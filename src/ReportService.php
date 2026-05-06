<?php
declare(strict_types=1);

namespace App;

final class ReportService
{
    public function __construct(private readonly CsvReader $reader) {}

    public function build(array $filters, int $campaignLimit = 10): array
    {
        $eventsById = [];
        $campaigns = [];
        $cities = [];
        $categories = [];

        foreach ($this->reader->read() as $row) {
            $cities[$row["city"]] = true;
            $categories[$row["category"]] = true;

            $isConfirmed = $row["status"] === "confirmed";

            if ($isConfirmed) {
                $campaign =
                    ($row["utm_campaign"] ?? "") !== ""
                        ? $row["utm_campaign"]
                        : "(brak)";
                $campaigns[$campaign] =
                    ($campaigns[$campaign] ?? 0) + (int) $row["ticket_qty"];
            }

            if (!$this->matchesFilters($row, $filters)) {
                continue;
            }

            $eventId = $row["event_id"];
            if (!isset($eventsById[$eventId])) {
                $eventsById[$eventId] = [
                    "event_id" => $eventId,
                    "event_date" => $row["event_date"],
                    "city" => $row["city"],
                    "category" => $row["category"],
                    "tickets_sold" => 0,
                ];
            }
            if ($isConfirmed) {
                $eventsById[$eventId]["tickets_sold"] +=
                    (int) $row["ticket_qty"];
            }
        }

        $events = array_values($eventsById);
        usort(
            $events,
            fn($a, $b) => strcmp($a["event_date"], $b["event_date"]),
        );

        arsort($campaigns);
        $top = array_slice($campaigns, 0, $campaignLimit, true);
        $topCampaigns = [];
        foreach ($top as $name => $sold) {
            $topCampaigns[] = [
                "utm_campaign" => $name,
                "tickets_sold" => $sold,
            ];
        }

        $citiesList = array_keys($cities);
        $categoriesList = array_keys($categories);
        sort($citiesList);
        sort($categoriesList);

        return [
            "events" => $events,
            "topCampaigns" => $topCampaigns,
            "options" => [
                "cities" => $citiesList,
                "categories" => $categoriesList,
            ],
        ];
    }

    private function matchesFilters(array $row, array $filters): bool
    {
        if (!empty($filters["city"]) && $row["city"] !== $filters["city"]) {
            return false;
        }
        if (
            !empty($filters["category"]) &&
            $row["category"] !== $filters["category"]
        ) {
            return false;
        }
        if (!empty($filters["from"]) && $row["event_date"] < $filters["from"]) {
            return false;
        }
        if (!empty($filters["to"]) && $row["event_date"] > $filters["to"]) {
            return false;
        }
        return true;
    }
}
