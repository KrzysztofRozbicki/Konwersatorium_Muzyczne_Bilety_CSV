<?php if (empty($events)): ?>
    <p class="empty">Brak wydarzeń pasujących do wybranych filtrów.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Miasto</th>
                <th>Kategoria</th>
                <th class="num">Sprzedane bilety</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= h($event["event_date"]) ?></td>
                    <td><?= h($event["city"]) ?></td>
                    <td><?= h($event["category"]) ?></td>
                    <td class="num"><?= (int) $event["tickets_sold"] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
