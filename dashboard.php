<?php
include "connection.php";

$selected = isset($_GET["category"]) && is_string($_GET["category"]) ? trim($_GET["category"]) : "";

try {
    // Totals for the stat boxes.
    $stats = $conn->query(
        "SELECT COUNT(*) AS made,
                COALESCE(SUM(sold), 0) AS sold,
                COALESCE(SUM(leather_kg), 0) AS leather
         FROM couches"
    )->fetch_assoc();

    // Categories with the number of couches still in stock.
    $categories = $conn->query(
        "SELECT c.name, c.width_cm, c.depth_cm, c.height_cm, COUNT(k.id) AS amount
         FROM categories c
         LEFT JOIN couches k ON k.category = c.name AND k.sold = 0
         GROUP BY c.name, c.width_cm, c.depth_cm, c.height_cm
         ORDER BY c.name"
    )->fetch_all(MYSQLI_ASSOC);

    // Couches in stock, optionally filtered by category.
    if ($selected !== "") {
        $stmt = $conn->prepare("SELECT name, category FROM couches WHERE sold = 0 AND category = ? ORDER BY name");
        $stmt->bind_param("s", $selected);
    } else {
        $stmt = $conn->prepare("SELECT name, category FROM couches WHERE sold = 0 ORDER BY category, name");
    }
    $stmt->execute();
    $couches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    error_log('Dashboard query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('The dashboard could not load its data. Has schema.sql been imported?');
}

$made = (int) $stats["made"];
$sold = (int) $stats["sold"];
$leather = rtrim(rtrim(number_format((float) $stats["leather"], 2, ".", ""), "0"), ".");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReLeather Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="dash-header">
    <div>
        <h1>ReLeather</h1>
        <p>Reused leather. New couches.</p>
    </div>
    <?php include "nav.php"; ?>
</header>

<div class="container">

    <h2>Dashboard</h2>

    <div class="dashboard">

        <div class="box">
            <h3>Couches Made</h3>
            <p><?= $made ?></p>
        </div>

        <div class="box">
            <h3>Couches Sold</h3>
            <p><?= $sold ?></p>
        </div>

        <div class="box">
            <h3>Leather Reused</h3>
            <p><?= $leather ?> kg</p>
        </div>

        <div class="box">
            <h3>In Stock</h3>
            <p><?= $made - $sold ?></p>
        </div>

    </div>

    <h2>Couch Categories</h2>

    <div class="categories">
        <?php foreach ($categories as $category): ?>
        <div class="category">
            <h3>Category <?= htmlspecialchars($category["name"]) ?></h3>
            <p>Width: <?= (int) $category["width_cm"] ?> cm</p>
            <p>Depth: <?= (int) $category["depth_cm"] ?> cm</p>
            <p>Height: <?= (int) $category["height_cm"] ?> cm</p>
            <p><strong>In stock: <?= (int) $category["amount"] ?> couches</strong></p>
            <a class="button button-small" href="dashboard.php?category=<?= urlencode($category["name"]) ?>#inventory-heading">View Couches</a>
        </div>
        <?php endforeach; ?>
    </div>

    <section class="records" aria-labelledby="inventory-heading">
        <h2 id="inventory-heading">Live inventory</h2>

        <?php if ($selected !== ""): ?>
        <p class="records-filter">
            Showing category <?= htmlspecialchars($selected) ?>.
            <a href="dashboard.php#inventory-heading">Show all</a>
        </p>
        <?php endif; ?>

        <?php if ($couches): ?>
            <?php foreach ($couches as $couch): ?>
            <div class="record">
                <strong><?= htmlspecialchars($couch["name"]) ?></strong>
                <p>Category: <?= htmlspecialchars($couch["category"]) ?></p>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="records-empty">No couches in stock<?= $selected !== "" ? " in this category" : "" ?>.</p>
        <?php endif; ?>
    </section>

</div>

</body>
</html>
