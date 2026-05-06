<form method="get" action="index.php" class="filters">
    <label>
        Miasto
        <select name="city">
            <option value="">— wszystkie —</option>
            <?php foreach ($options["cities"] as $city): ?>
                <option value="<?= h($city) ?>" <?= $filters["city"] === $city
    ? "selected"
    : "" ?>>
                    <?= h($city) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Kategoria
        <select name="category">
            <option value="">— wszystkie —</option>
            <?php foreach ($options["categories"] as $cat): ?>
                <option value="<?= h($cat) ?>" <?= $filters["category"] === $cat
    ? "selected"
    : "" ?>>
                    <?= h($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Data od
        <input type="date" name="from" value="<?= h($filters["from"]) ?>">
    </label>

    <label>
        Data do
        <input type="date" name="to" value="<?= h($filters["to"]) ?>">
    </label>

    <div class="actions">
        <a href="index.php" class="btn-secondary">Wyczyść filtry</a>
    </div>
</form>
