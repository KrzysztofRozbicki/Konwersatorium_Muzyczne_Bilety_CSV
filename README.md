# Ticket Sales

Aplikacja PHP do przeglądania sprzedaży biletów z pliku CSV.

## Wymagania

- PHP 8.1 lub nowszy

## Uruchomienie
bash / terminal
``` 
cd ticket-sales
php -S localhost:8000 -t public
```

Otwórz w przeglądarce <http://localhost:8000>.

## Dane

Domyślny plik z przykładowymi danymi: `data/tickets.csv`.

Aby wgrać własny CSV, użyj formularza w sekcji "Źródło danych" na stronie głównej.
Po wgraniu zobaczysz podgląd — kliknij "Aktywuj", żeby użyć pliku jako źródła.
Można przetestować po testowych mockupach wgranych w folder data/

Wymagane kolumny: `event_id`, `event_date`, `city`, `category`, `ticket_qty`, `status`.
W przypadku błędu struktury pliku csv pojawi się komunikat informujący o błędzie i jak go naprawić.
Limit: 10 MB.
