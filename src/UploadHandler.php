<?php
declare(strict_types=1);

namespace App;

final class UploadHandler
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;
    private const ALLOWED_MIME = [
        "text/csv",
        "text/plain",
        "application/csv",
        "application/vnd.ms-excel",
    ];

    public function __construct(private readonly string $defaultCsvPath) {}

    public function handleUpload(array $file): array
    {
        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                $this->uploadErrorMessage($file["error"]),
            );
        }

        if ($file["size"] > self::MAX_SIZE_BYTES) {
            throw new \RuntimeException("Plik jest za duży (limit: 10 MB).");
        }

        if (!is_uploaded_file($file["tmp_name"])) {
            throw new \RuntimeException("Nieprawidłowe źródło pliku.");
        }
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, ["csv", "tsv", "txt"], true)) {
            throw new \RuntimeException(
                "Dozwolone rozszerzenia: .csv, .tsv, .txt",
            );
        }

        // Sniff the actual content type — extension alone is trivially spoofed.
        if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo
                ? finfo_file($finfo, $file["tmp_name"])
                : false;
            if ($finfo) {
                finfo_close($finfo);
            }
            if (
                $detectedMime !== false &&
                !in_array($detectedMime, self::ALLOWED_MIME, true)
            ) {
                throw new \RuntimeException(
                    "Nieobsługiwany typ pliku: {$detectedMime}. Dozwolone: CSV / tekst.",
                );
            }
        }

        $sessionId = session_id();
        if ($sessionId === "" || $sessionId === false) {
            throw new \RuntimeException("Brak aktywnej sesji.");
        }

        $targetPath =
            sys_get_temp_dir() .
            "/ticketsales_" .
            $sessionId .
            "_" .
            bin2hex(random_bytes(4)) .
            ".csv";
        if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
            throw new \RuntimeException("Nie udało się zapisać pliku.");
        }

        try {
            $reader = new CsvReader($targetPath);
            $info = $reader->validate();
        } catch (\Throwable $e) {
            @unlink($targetPath);
            throw $e;
        }

        $this->clearPending();

        $_SESSION["pending_csv"] = [
            "path" => $targetPath,
            "original" => $file["name"],
            "rows" => $info["rows"],
            "columns" => $info["columns"],
            "uploaded_at" => time(),
        ];

        return $info;
    }

    public function activate(): void
    {
        $pending = $_SESSION["pending_csv"] ?? null;
        if ($pending === null) {
            throw new \RuntimeException("Brak pliku do aktywacji.");
        }

        $current = $_SESSION["active_csv"] ?? null;
        if (
            $current !== null &&
            $current["path"] !== $pending["path"] &&
            is_file($current["path"])
        ) {
            @unlink($current["path"]);
        }

        $_SESSION["active_csv"] = $pending;
        unset($_SESSION["pending_csv"]);
    }

    public function discardPending(): void
    {
        $this->clearPending();
    }

    public function resetToDefault(): void
    {
        if (
            !empty($_SESSION["active_csv"]["path"]) &&
            is_file($_SESSION["active_csv"]["path"])
        ) {
            @unlink($_SESSION["active_csv"]["path"]);
        }
        unset($_SESSION["active_csv"]);
        $this->clearPending();
    }

    public function getActivePath(): string
    {
        $active = $_SESSION["active_csv"] ?? null;
        if ($active !== null && is_file($active["path"])) {
            return $active["path"];
        }
        return $this->defaultCsvPath;
    }

    public function getActiveInfo(): ?array
    {
        return $_SESSION["active_csv"] ?? null;
    }

    public function getPendingInfo(): ?array
    {
        return $_SESSION["pending_csv"] ?? null;
    }

    private function clearPending(): void
    {
        $pending = $_SESSION["pending_csv"] ?? null;
        if ($pending !== null && is_file($pending["path"])) {
            @unlink($pending["path"]);
        }
        unset($_SESSION["pending_csv"]);
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Plik jest za duży.",
            UPLOAD_ERR_PARTIAL => "Plik został przesłany tylko częściowo.",
            UPLOAD_ERR_NO_FILE => "Nie wybrano pliku.",
            UPLOAD_ERR_NO_TMP_DIR => "Brak katalogu tymczasowego na serwerze.",
            UPLOAD_ERR_CANT_WRITE => "Nie udało się zapisać pliku.",
            UPLOAD_ERR_EXTENSION
                => "Upload zablokowany przez rozszerzenie PHP.",
            default => "Nieznany błąd uploadu.",
        };
    }
}
