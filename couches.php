<?php
session_start(["cookie_httponly" => true, "cookie_samesite" => "Lax"]);
include "connection.php";

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function redirectWith(string $type, string $message): void
{
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
    header("Location: couches.php", true, 303);
    exit;
}

function findCouch(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT id, name, category, leather_kg, sold FROM couches WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Protects the forms against requests sent from other websites.
$_SESSION["csrf"] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION["csrf"];

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);

$errors = [];
$form = ["id" => null, "name" => "", "category" => "", "leather_kg" => "", "sold" => 0];
$deleting = null;

try {
    $categories = array_column(
        $conn->query("SELECT name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC),
        "name"
    );

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $token = $_POST["csrf"] ?? "";
        if (!is_string($token) || !hash_equals($csrf, $token)) {
            redirectWith("error", "Your session expired. Please try again.");
        }

        $action = $_POST["action"] ?? "";
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if ($action === "save") {
            $form = [
                "id" => $id ?: null,
                "name" => trim(is_string($_POST["name"] ?? null) ? $_POST["name"] : ""),
                "category" => is_string($_POST["category"] ?? null) ? $_POST["category"] : "",
                // Accept both 5.5 and the Dutch notation 5,5.
                "leather_kg" => str_replace(",", ".", trim(is_string($_POST["leather_kg"] ?? null) ? $_POST["leather_kg"] : "")),
                "sold" => isset($_POST["sold"]) ? 1 : 0,
            ];

            if ($form["name"] === "") {
                $errors["name"] = "Enter a name.";
            } elseif (mb_strlen($form["name"]) > 100) {
                $errors["name"] = "Use 100 characters or fewer.";
            }

            if (!in_array($form["category"], $categories, true)) {
                $errors["category"] = "Choose a category.";
            }

            if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $form["leather_kg"])) {
                $errors["leather_kg"] = "Enter a weight from 0 to 9999.99 kg.";
            }

            if ($form["id"] !== null && !findCouch($conn, $form["id"])) {
                redirectWith("error", "That couch no longer exists.");
            }

            if (!$errors) {
                if ($form["id"] === null) {
                    $stmt = $conn->prepare("INSERT INTO couches (name, category, leather_kg, sold) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $form["name"], $form["category"], $form["leather_kg"], $form["sold"]);
                    $stmt->execute();
                    redirectWith("success", "Added " . $form["name"] . ".");
                }

                $stmt = $conn->prepare("UPDATE couches SET name = ?, category = ?, leather_kg = ?, sold = ? WHERE id = ?");
                $stmt->bind_param("sssii", $form["name"], $form["category"], $form["leather_kg"], $form["sold"], $form["id"]);
                $stmt->execute();
                redirectWith("success", "Saved " . $form["name"] . ".");
            }

            http_response_code(422);
        } elseif ($action === "toggle" && $id) {
            $couch = findCouch($conn, $id);
            if (!$couch) {
                redirectWith("error", "That couch no longer exists.");
            }

            $stmt = $conn->prepare("UPDATE couches SET sold = 1 - sold WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirectWith("success", $couch["name"] . ($couch["sold"] ? " is back in stock." : " is marked as sold."));
        } elseif ($action === "delete" && $id) {
            $couch = findCouch($conn, $id);
            if (!$couch) {
                redirectWith("error", "That couch no longer exists.");
            }

            $stmt = $conn->prepare("DELETE FROM couches WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            redirectWith("success", "Deleted " . $couch["name"] . ".");
        } else {
            redirectWith("error", "That action is not available.");
        }
    } else {
        $editId = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $deleteId = filter_input(INPUT_GET, "delete", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if ($editId) {
            $form = findCouch($conn, $editId) ?? redirectWith("error", "That couch no longer exists.");
        } elseif ($deleteId) {
            $deleting = findCouch($conn, $deleteId) ?? redirectWith("error", "That couch no longer exists.");
        }
    }

    $couches = $conn->query(
        "SELECT id, name, category, leather_kg, sold FROM couches ORDER BY category, name"
    )->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    error_log("Couch management failed: " . $e->getMessage());
    http_response_code(500);
    exit("The couches could not be loaded or saved. Has schema.sql been imported?");
}

$isEditing = $form["id"] !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Couches | ReLeather</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="dash-header">
    <div>
        <h1>ReLeather</h1>
        <p>Manage couches</p>
    </div>
    <div class="header-links">
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link" href="dashboard.php">Dashboard</a>
    </div>
</header>

<div class="container">

    <?php if ($flash): ?>
    <p class="flash flash-<?= e($flash["type"]) ?>" role="status"><?= e($flash["message"]) ?></p>
    <?php endif; ?>

    <?php if ($deleting): ?>
    <section class="confirm" aria-labelledby="confirm-heading">
        <h2 id="confirm-heading">Delete <?= e($deleting["name"]) ?>?</h2>
        <p>This permanently removes the couch and cannot be undone.</p>
        <div class="row-actions">
            <form method="post" action="couches.php">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $deleting["id"] ?>">
                <button class="button button-small button-danger" type="submit">Delete couch</button>
            </form>
            <a class="button button-small button-quiet" href="couches.php">Keep couch</a>
        </div>
    </section>
    <?php endif; ?>

    <section class="manage-form" id="couch-form" aria-labelledby="form-heading">
        <h2 id="form-heading"><?= $isEditing ? "Edit couch" : "Add a couch" ?></h2>

        <form method="post" action="couches.php#couch-form" novalidate>
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($isEditing): ?>
            <input type="hidden" name="id" value="<?= (int) $form["id"] ?>">
            <?php endif; ?>

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" maxlength="100" required value="<?= e($form["name"]) ?>"<?= isset($errors["name"]) ? ' aria-invalid="true" aria-describedby="name-error"' : "" ?>>
                <?php if (isset($errors["name"])): ?><p class="field-error" id="name-error"><?= e($errors["name"]) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required<?= isset($errors["category"]) ? ' aria-invalid="true" aria-describedby="category-error"' : "" ?>>
                    <option value="">Choose…</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>"<?= $form["category"] === $category ? " selected" : "" ?>>Category <?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors["category"])): ?><p class="field-error" id="category-error"><?= e($errors["category"]) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label for="leather_kg">Leather (kg)</label>
                <input id="leather_kg" name="leather_kg" type="text" inputmode="decimal" required value="<?= e($form["leather_kg"]) ?>"<?= isset($errors["leather_kg"]) ? ' aria-invalid="true" aria-describedby="leather-error"' : "" ?>>
                <?php if (isset($errors["leather_kg"])): ?><p class="field-error" id="leather-error"><?= e($errors["leather_kg"]) ?></p><?php endif; ?>
            </div>

            <label class="check">
                <input type="checkbox" name="sold" value="1"<?= $form["sold"] ? " checked" : "" ?>>
                Already sold
            </label>

            <div class="row-actions">
                <button class="button button-small" type="submit"><?= $isEditing ? "Save changes" : "Add couch" ?></button>
                <?php if ($isEditing): ?>
                <a class="button button-small button-quiet" href="couches.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="records" aria-labelledby="all-couches">
        <h2 id="all-couches">All couches (<?= count($couches) ?>)</h2>

        <?php if ($couches): ?>
        <div class="table-wrap">
            <table class="couch-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col" class="numeric">Leather</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($couches as $couch): ?>
                    <tr>
                        <th scope="row"><?= e($couch["name"]) ?></th>
                        <td><?= e($couch["category"]) ?></td>
                        <td class="numeric"><?= e(rtrim(rtrim($couch["leather_kg"], "0"), ".") ?: "0") ?> kg</td>
                        <td><?= $couch["sold"] ? "Sold" : "In stock" ?></td>
                        <td>
                            <div class="row-actions">
                                <form method="post" action="couches.php">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $couch["id"] ?>">
                                    <button class="text-action" type="submit" aria-label="<?= e(($couch["sold"] ? "Return to stock: " : "Mark as sold: ") . $couch["name"]) ?>"><?= $couch["sold"] ? "Return to stock" : "Mark sold" ?></button>
                                </form>
                                <a class="text-action" href="couches.php?edit=<?= (int) $couch["id"] ?>#couch-form" aria-label="Edit <?= e($couch["name"]) ?>">Edit</a>
                                <a class="text-action text-danger" href="couches.php?delete=<?= (int) $couch["id"] ?>" aria-label="Delete <?= e($couch["name"]) ?>">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="records-empty">No couches yet. Add the first one above.</p>
        <?php endif; ?>
    </section>

</div>

</body>
</html>
