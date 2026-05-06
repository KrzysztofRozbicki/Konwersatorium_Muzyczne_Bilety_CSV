# Ticket Sales

Aplikacja PHP (SSR - server side redner) do przeglądania sprzedaży biletów z pliku CSV.

## Wymagania

- PHP 8.1 lub nowszy

## Uruchomienie
bash / terminal
``` 
gh repo clone KrzysztofRozbicki/Konwersatorium_Muzyczne_Rekrutacja
cd Konwersatorium_Muzyczne_Rekrutacja
php -S localhost:8000 -t public
```

Otwórz w przeglądarce <http://localhost:8000>.

## Dane

Domyślny plik z przykładowymi danymi: `data/tickets.csv`.

Aby wgrać własny CSV, użyj formularza w sekcji "Źródło danych" na stronie głównej.
Po wgraniu zobaczysz podgląd — kliknij "Aktywuj", żeby użyć pliku jako źródła.
Można przetestować po testowych mockupach wgranych w folder `data/test-data/`

Wymagane kolumny: `event_id`, `event_date`, `city`, `category`, `order_id`,
`ticket_qty`, `status`, `utm_source`, `utm_campaign`, `utm_content`, `sold_out`.

W przypadku błędu struktury pliku CSV pojawi się komunikat z informacją o problemie.
Limit rozmiaru: 10 MB.

## Funkcjonalność

- Lista wydarzeń z sumą sprzedanych biletów (tylko `status = confirmed`)
- Top 10 kampanii UTM po liczbie sprzedanych biletów
- Filtrowanie po mieście, kategorii i zakresie dat
- Auto-detekcja separatora (`,` `;` `tab` `|`), kodowania (UTF-8 / Windows-1250)
  i formatów dat, aby akceptowało różne pliki csv

## Czas realizacji

~5 godzin
