<?php
declare(strict_types=1);

session_start();

spl_autoload_register(function (string $class): void {
    $prefix = "App\\";
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . "/../src/" . str_replace("\\", "/", $relative) . ".php";
    if (is_file($file)) {
        require $file;
    }
});

use App\CsvReader;
use App\ReportService;
use App\UploadHandler;

const DEFAULT_CSV = __DIR__ . "/../data/tickets.csv";

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function sanitizeDate(?string $date): ?string
{
    if ($date === null || $date === "") {
        return null;
    }
    $d = \DateTime::createFromFormat("Y-m-d", $date);
    return $d && $d->format("Y-m-d") === $date ? $date : null;
}

function flash(string $type, string $message): void
{
    $_SESSION["flash"][] = ["type" => $type, "message" => $message];
}

function getFlash(): array
{
    $flash = $_SESSION["flash"] ?? [];
    unset($_SESSION["flash"]);
    return $flash;
}

function redirect(string $url): never
{
    header("Location: {$url}");
    exit();
}

$uploadHandler = new UploadHandler(DEFAULT_CSV);
$action = $_POST["action"] ?? null;

if ($action !== null) {
    try {
        match ($action) {
            "upload" => $uploadHandler->handleUpload($_FILES["csv_file"] ?? []),
            "activate" => $uploadHandler->activate(),
            "discard" => $uploadHandler->discardPending(),
            "reset" => $uploadHandler->resetToDefault(),
            default => throw new \RuntimeException("Nieznana akcja."),
        };

        match ($action) {
            "upload" => flash(
                "success",
                "Plik załadowany. Sprawdź podgląd i potwierdź aktywację.",
            ),
            "activate" => flash(
                "success",
                "Plik aktywowany jako źródło danych.",
            ),
            "discard" => flash("info", "Załadowany plik został odrzucony."),
            "reset" => flash("info", "Wrócono do domyślnego źródła danych."),
        };
    } catch (\Throwable $e) {
        flash("error", $e->getMessage());
    }

    redirect("index.php");
}

$filters = [
    "city" => isset($_GET["city"]) ? trim((string) $_GET["city"]) : "",
    "category" => isset($_GET["category"])
        ? trim((string) $_GET["category"])
        : "",
    "from" => sanitizeDate($_GET["from"] ?? null) ?? "",
    "to" => sanitizeDate($_GET["to"] ?? null) ?? "",
];
if (!in_array($filters["category"], ["", "kids", "adults"], true)) {
    $filters["category"] = "";
}

$activePath = $uploadHandler->getActivePath();
$activeInfo = $uploadHandler->getActiveInfo();
$pendingInfo = $uploadHandler->getPendingInfo();

try {
    $report = new ReportService(new CsvReader($activePath))->build(
        $filters,
        10,
    );
    $events = $report["events"];
    $topCampaigns = $report["topCampaigns"];
    $options = $report["options"];

    $previewRows = [];
    if ($pendingInfo !== null) {
        $previewReader = new CsvReader($pendingInfo["path"]);
        foreach ($previewReader->read() as $i => $row) {
            $previewRows[] = $row;
            if ($i >= 4) {
                break;
            }
        }
    }
} catch (\Throwable $e) {
    flash("error", "Błąd odczytu pliku: " . $e->getMessage());
    $events = $topCampaigns = [];
    $options = ["cities" => [], "categories" => []];
    $previewRows = [];
}

$flashMessages = getFlash();

require __DIR__ . "/../views/layout.php";
