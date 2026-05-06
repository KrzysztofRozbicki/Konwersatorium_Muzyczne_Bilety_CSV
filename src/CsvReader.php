<?php
declare(strict_types=1);

namespace App;

final class CsvReader
{
    private const REQUIRED_COLUMNS = [
        "event_id",
        "event_date",
        "city",
        "category",
        "order_id",
        "ticket_qty",
        "status",
        "utm_source",
        "utm_campaign",
        "utm_content",
        "sold_out",
    ];

    private const SEPARATORS = [",", ";", "\t", "|"];
    private const TRUE_VALUES = ["true", "1", "tak", "yes", "y", "t"];
    private const FALSE_VALUES = ["false", "0", "nie", "no", "n", "f"];

    private ?array $dialect = null;

    public function __construct(private readonly string $filePath)
    {
        if (!is_readable($this->filePath)) {
            throw new \RuntimeException(
                "Nie można odczytać pliku CSV: {$this->filePath}",
            );
        }
    }

    public function read(): \Generator
    {
        $dialect = $this->detectDialect();

        $handle = fopen($this->filePath, "r");
        if ($handle === false) {
            throw new \RuntimeException(
                "Nie udało się otworzyć pliku: {$this->filePath}",
            );
        }

        try {
            $headerLine = fgets($handle);
            if ($headerLine === false) {
                return;
            }

            while (($line = fgets($handle)) !== false) {
                $line = $this->stripBom($line);
                $decoded = $this->decodeLine($line, $dialect["encoding"]);
                $row = $this->parseLine(
                    $decoded,
                    $dialect["separator"],
                    $dialect["enclosure"],
                );

                if (count($row) === 0) {
                    continue;
                }

                $normalized = $this->normalizeRow($row, $dialect["column_map"]);
                if ($normalized !== null) {
                    yield $normalized;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    public function validate(): array
    {
        $dialect = $this->detectDialect();

        $foundColumns = array_filter($dialect["column_map"]);
        $missing = array_diff(self::REQUIRED_COLUMNS, $foundColumns);
        if (!empty($missing)) {
            throw new \RuntimeException(
                "W pliku brakuje wymaganych kolumn: " .
                    implode(", ", $missing) .
                    ". Wykryte kolumny: " .
                    implode(", ", array_unique($foundColumns)),
            );
        }

        $count = 0;
        foreach ($this->read() as $_) {
            $count++;
        }

        if ($count === 0) {
            throw new \RuntimeException(
                "Plik CSV nie zawiera żadnych poprawnych rekordów.",
            );
        }

        return [
            "rows" => $count,
            "columns" => array_values(array_unique($foundColumns)),
        ];
    }

    private function detectDialect(): array
    {
        if ($this->dialect !== null) {
            return $this->dialect;
        }

        $handle = fopen($this->filePath, "r");
        if ($handle === false) {
            throw new \RuntimeException(
                "Nie udało się otworzyć pliku: {$this->filePath}",
            );
        }
        $sample = fread($handle, 8192) ?: "";
        fclose($handle);

        $sample = $this->stripBom($sample);
        $encoding = $this->detectEncoding($sample);
        $sample = $this->decodeLine($sample, $encoding);

        $firstLine = strtok($sample, "\r\n") ?: "";
        $enclosure = $this->detectEnclosure($firstLine);
        $separator = $this->detectSeparator($firstLine, $enclosure);

        $headers = $this->parseLine($firstLine, $separator, $enclosure);
        $columnMap = $this->mapColumns($headers);

        $this->dialect = [
            "separator" => $separator,
            "enclosure" => $enclosure,
            "encoding" => $encoding,
            "column_map" => $columnMap,
        ];

        return $this->dialect;
    }

    private function detectEncoding(string $sample): string
    {
        if (mb_check_encoding($sample, "UTF-8")) {
            return "UTF-8";
        }
        foreach (["Windows-1250", "ISO-8859-2"] as $enc) {
            $converted = @iconv($enc, "UTF-8", $sample);
            if (
                $converted !== false &&
                mb_check_encoding($converted, "UTF-8")
            ) {
                return $enc;
            }
        }
        return "UTF-8";
    }

    private function detectSeparator(string $line, string $enclosure): string
    {
        $best = ",";
        $bestCount = 0;

        foreach (self::SEPARATORS as $sep) {
            $count = $this->countOutsideQuotes($line, $sep, $enclosure);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $sep;
            }
        }
        return $best;
    }

    private function detectEnclosure(string $line): string
    {
        $doubleQuotes = substr_count($line, '"');
        $singleQuotes = substr_count($line, "'");

        if ($singleQuotes >= 2 && $singleQuotes > $doubleQuotes) {
            return "'";
        }
        return '"';
    }

    private function countOutsideQuotes(
        string $line,
        string $needle,
        string $enclosure,
    ): int {
        $count = 0;
        $inQuotes = false;
        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];
            if ($ch === $enclosure) {
                $inQuotes = !$inQuotes;
                continue;
            }
            if (!$inQuotes && $ch === $needle) {
                $count++;
            }
        }
        return $count;
    }

    private function mapColumns(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $header) {
            $name = strtolower(trim($header));
            $map[$idx] = in_array($name, self::REQUIRED_COLUMNS, true)
                ? $name
                : null;
        }
        return $map;
    }

    private function parseLine(
        string $line,
        string $separator,
        string $enclosure,
    ): array {
        $line = rtrim($line, "\r\n");
        if ($line === "") {
            return [];
        }
        return str_getcsv($line, $separator, $enclosure);
    }

    private function normalizeRow(array $row, array $columnMap): ?array
    {
        $result = [];
        foreach ($columnMap as $idx => $canonical) {
            if ($canonical === null) {
                continue;
            }
            $value = $row[$idx] ?? "";
            $result[$canonical] = $this->normalizeValue($canonical, $value);
        }

        foreach (self::REQUIRED_COLUMNS as $col) {
            if (
                !array_key_exists($col, $result) ||
                $result[$col] === null ||
                $result[$col] === ""
            ) {
                return null;
            }
        }

        return $result;
    }

    private function normalizeValue(
        string $canonical,
        string $value,
    ): string|int|bool|null {
        $value = trim($value);
        if ($value === "") {
            return null;
        }

        return match ($canonical) {
            "event_date" => $this->normalizeDate($value),
            "ticket_qty" => (int) $value,
            "sold_out" => $this->normalizeBool($value),
            default => $value,
        };
    }

    private function normalizeDate(string $value): ?string
    {
        $formats = ["Y-m-d", "Y/m/d", "d.m.Y", "d-m-Y", "d/m/Y", "m/d/Y"];
        foreach ($formats as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $value);
            if ($d && $d->format($fmt) === $value) {
                return $d->format("Y-m-d");
            }
        }
        return null;
    }

    private function normalizeBool(string $value): ?bool
    {
        $v = strtolower($value);
        if (in_array($v, self::TRUE_VALUES, true)) {
            return true;
        }
        if (in_array($v, self::FALSE_VALUES, true)) {
            return false;
        }
        return null;
    }

    private function stripBom(string $s): string
    {
        if (str_starts_with($s, "\xEF\xBB\xBF")) {
            return substr($s, 3);
        }
        return $s;
    }

    private function decodeLine(string $line, string $encoding): string
    {
        if ($encoding === "UTF-8") {
            return $line;
        }
        $converted = @iconv($encoding, "UTF-8//IGNORE", $line);
        return $converted === false ? $line : $converted;
    }
}
