<div class="upload-info">
    <?php if ($activeInfo !== null): ?>
        <p>
            Aktywny plik: <strong><?= h($activeInfo["original"]) ?></strong>
            <span class="muted">(<?= (int) $activeInfo[
                "rows"
            ] ?> rekordów)</span>
        </p>
        <form method="post" action="index.php" class="inline-form">
            <input type="hidden" name="action" value="reset">
            <button type="submit" class="btn-secondary">Wróć do domyślnego pliku</button>
        </form>
    <?php else: ?>
        <p class="muted">Aktualnie używany jest domyślny plik <code>data/tickets.csv</code>.</p>
    <?php endif; ?>
</div>

<form method="post" action="index.php" enctype="multipart/form-data" class="upload-form">
    <input type="hidden" name="action" value="upload">
    <label>
        Załaduj własny plik CSV
        <input type="file" name="csv_file" accept=".csv,.tsv,.txt" required>
    </label>
    <noscript><button type="submit">Wgraj</button></noscript>
    <p class="muted hint">
        Auto-detekcja: separator (<code>,</code> <code>;</code> <code>tab</code> <code>|</code>),
        encoding (UTF-8 / Windows-1250), formatów dat i wartości true/false.
    </p>
</form>
