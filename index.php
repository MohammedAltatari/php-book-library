<?php
session_start();

// Allowed genres
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// Initialize books in session
if (!isset($_SESSION["books"])) {

    $_SESSION["books"] = [

        [
            "id" => 1,
            "title" => "Clean Code",
            "author" => "Robert Martin",
            "genre" => "Technology",
            "year" => 2008,
            "pages" => 464
        ],

        [
            "id" => 2,
            "title" => "Atomic Habits",
            "author" => "James Clear",
            "genre" => "Non-Fiction",
            "year" => 2018,
            "pages" => 320
        ],

        [
            "id" => 3,
            "title" => "The Hobbit",
            "author" => "J.R.R Tolkien",
            "genre" => "Fiction",
            "year" => 1937,
            "pages" => 310
        ]

    ];
}

$books = $_SESSION["books"];

$errors = [];
$submittedData = [];

$editMode = false;
$editId = null;

// Edit Mode
if (isset($_GET["edit_id"])) {

    $editId = (int) $_GET["edit_id"];

    foreach ($books as $book) {

        if ($book["id"] == $editId) {

            $editMode = true;
            $submittedData = $book;

            break;
        }
    }
}

// Handle Form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Delete Book
    if (isset($_POST["action"]) && $_POST["action"] === "delete") {

        $deleteId = (int) $_POST["book_id"];

        $newBooks = [];

        foreach ($books as $book) {

            if ($book["id"] != $deleteId) {
                $newBooks[] = $book;
            }
        }

        // Re-arrange IDs
        foreach ($newBooks as $index => &$book) {
            $book["id"] = $index + 1;
        }

        unset($book);

        $_SESSION["books"] = $newBooks;

        $_SESSION["success"] = "Book deleted successfully.";

        header("Location: index.php");
        exit;
    }

    // Sanitize Data
    $submittedData["title"] = htmlspecialchars(trim($_POST["title"] ?? ""));
    $submittedData["author"] = htmlspecialchars(trim($_POST["author"] ?? ""));
    $submittedData["genre"] = htmlspecialchars(trim($_POST["genre"] ?? ""));
    $submittedData["year"] = htmlspecialchars(trim($_POST["year"] ?? ""));
    $submittedData["pages"] = htmlspecialchars(trim($_POST["pages"] ?? ""));

    $formAction = $_POST["action"] ?? "add";

    $editId = isset($_POST["book_id"])
        ? (int) $_POST["book_id"]
        : null;

    // Title Validation
    if (empty($submittedData["title"])) {

        $errors["title"] = "Title is required.";

    } elseif (
        strlen($submittedData["title"]) < 3 ||
        strlen($submittedData["title"]) > 120
    ) {

        $errors["title"] = "Title must be between 3 and 120 characters.";
    }

    // Author Validation
    if (empty($submittedData["author"])) {

        $errors["author"] = "Author is required.";

    } elseif (str_word_count($submittedData["author"]) < 2) {

        $errors["author"] = "Author must contain first and last name.";
    }

    // Genre Validation
    if (empty($submittedData["genre"])) {

        $errors["genre"] = "Genre is required.";

    } elseif (!in_array($submittedData["genre"], $genres)) {

        $errors["genre"] = "Invalid genre selected.";
    }

    // Year Validation
    $currentYear = date("Y");

    if (empty($submittedData["year"])) {

        $errors["year"] = "Year is required.";

    } elseif (
        !ctype_digit($submittedData["year"]) ||
        strlen($submittedData["year"]) != 4 ||
        $submittedData["year"] < 1000 ||
        $submittedData["year"] > $currentYear
    ) {

        $errors["year"] = "Year must be between 1000 and current year.";
    }

    // Pages Validation
    if (empty($submittedData["pages"])) {

        $errors["pages"] = "Pages field is required.";

    } elseif (
        !ctype_digit($submittedData["pages"]) ||
        $submittedData["pages"] <= 0
    ) {

        $errors["pages"] = "Pages must be greater than 0.";
    }

    // If Validation Success
    if (empty($errors)) {

        // Update Book
        if ($formAction === "update") {

            foreach ($books as &$book) {

                if ($book["id"] == $editId) {

                    $book["title"] = $submittedData["title"];
                    $book["author"] = $submittedData["author"];
                    $book["genre"] = $submittedData["genre"];
                    $book["year"] = (int) $submittedData["year"];
                    $book["pages"] = (int) $submittedData["pages"];

                    break;
                }
            }

            unset($book);

            $_SESSION["success"] = "Book updated successfully.";

        } else {

            // Generate New ID
            $maxId = 0;

            foreach ($books as $book) {

                if ($book["id"] > $maxId) {
                    $maxId = $book["id"];
                }
            }

            // Add Book
            $books[] = [
                "id" => $maxId + 1,
                "title" => $submittedData["title"],
                "author" => $submittedData["author"],
                "genre" => $submittedData["genre"],
                "year" => (int) $submittedData["year"],
                "pages" => (int) $submittedData["pages"]
            ];

            $_SESSION["success"] = "Book added successfully.";
        }

        $_SESSION["books"] = $books;

        header("Location: index.php");
        exit;
    }

    if ($formAction === "update") {
        $editMode = true;
    }
}

// Search / Filter
$searchTerm = htmlspecialchars(trim($_GET["search"] ?? ""));
$displayBooks = $books;

if (!empty($searchTerm)) {

    $displayBooks = [];

    foreach ($books as $book) {

        if (
            stripos($book["title"], $searchTerm) !== false ||
            stripos($book["author"], $searchTerm) !== false
        ) {
            $displayBooks[] = $book;
        }
    }
}

// Success Message
$success = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>PHP Book Library</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container mt-5">

    <h1 class="text-center mb-4">
        PHP Book Library
    </h1>

    <!-- Success Alert -->
    <?php if (!empty($success)): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <?= htmlspecialchars($success) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <div class="row">

        <!-- Form Section -->
        <div class="col-md-4">

            <div class="card p-3">

                <h3 class="mb-3">

                    <?= $editMode ? "Edit Book" : "Add Book" ?>

                </h3>

                <!-- Error Alert -->
                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">
                        Please fix the errors below.
                    </div>

                <?php endif; ?>

                <form method="POST" action="index.php">

                    <input
                        type="hidden"
                        name="action"
                        value="<?= $editMode ? 'update' : 'add' ?>"
                    >

                    <?php if ($editMode): ?>

                        <input
                            type="hidden"
                            name="book_id"
                            value="<?= htmlspecialchars($editId) ?>"
                        >

                    <?php endif; ?>

                    <!-- Title -->
                    <div class="mb-3">

                        <label class="form-label">
                            Title
                        </label>

                        <input
                            type="text"
                            name="title"

                            class="form-control <?= isset($errors["title"]) ? 'is-invalid' : '' ?>"

                            value="<?= htmlspecialchars($submittedData["title"] ?? '') ?>"
                        >

                        <?php if (isset($errors["title"])): ?>

                            <div class="invalid-feedback">

                                <?= htmlspecialchars($errors["title"]) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Author -->
                    <div class="mb-3">

                        <label class="form-label">
                            Author
                        </label>

                        <input
                            type="text"
                            name="author"

                            class="form-control <?= isset($errors["author"]) ? 'is-invalid' : '' ?>"

                            value="<?= htmlspecialchars($submittedData["author"] ?? '') ?>"
                        >

                        <?php if (isset($errors["author"])): ?>

                            <div class="invalid-feedback">

                                <?= htmlspecialchars($errors["author"]) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Genre -->
                    <div class="mb-3">

                        <label class="form-label">
                            Genre
                        </label>

                        <select
                            name="genre"

                            class="form-control <?= isset($errors["genre"]) ? 'is-invalid' : '' ?>"
                        >

                            <option value="">
                                Select Genre
                            </option>

                            <?php foreach ($genres as $genre): ?>

                                <option
                                    value="<?= htmlspecialchars($genre) ?>"

                                    <?= ($submittedData["genre"] ?? '') === $genre ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($genre) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($errors["genre"])): ?>

                            <div class="invalid-feedback">

                                <?= htmlspecialchars($errors["genre"]) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Year -->
                    <div class="mb-3">

                        <label class="form-label">
                            Year
                        </label>

                        <input
                            type="number"
                            name="year"

                            class="form-control <?= isset($errors["year"]) ? 'is-invalid' : '' ?>"

                            value="<?= htmlspecialchars($submittedData["year"] ?? '') ?>"
                        >

                        <?php if (isset($errors["year"])): ?>

                            <div class="invalid-feedback">

                                <?= htmlspecialchars($errors["year"]) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Pages -->
                    <div class="mb-3">

                        <label class="form-label">
                            Pages
                        </label>

                        <input
                            type="number"
                            name="pages"

                            class="form-control <?= isset($errors["pages"]) ? 'is-invalid' : '' ?>"

                            value="<?= htmlspecialchars($submittedData["pages"] ?? '') ?>"
                        >

                        <?php if (isset($errors["pages"])): ?>

                            <div class="invalid-feedback">

                                <?= htmlspecialchars($errors["pages"]) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >

                        <?= $editMode ? "Update Book" : "Add Book" ?>

                    </button>

                    <?php if ($editMode): ?>

                        <a
                            href="index.php"
                            class="btn btn-secondary w-100 mt-2"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </form>

            </div>

        </div>

        <!-- Table Section -->
        <div class="col-md-8">

            <div class="card p-3">

                <h3 class="mb-3">
                    Books Table
                </h3>

                <!-- Search Form -->
                <form method="GET" action="index.php" class="mb-3">

                    <div class="input-group">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by title or author"
                            value="<?= htmlspecialchars($searchTerm) ?>"
                        >

                        <button class="btn btn-outline-primary" type="submit">
                            Search
                        </button>

                        <a href="index.php" class="btn btn-outline-secondary">
                            Clear
                        </a>

                    </div>

                </form>

                <table class="table table-striped table-hover table-bordered">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                            <th>Year</th>
                            <th>Pages</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($displayBooks)): ?>

                        <tr>

                            <td colspan="7" class="text-center">
                                No books found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    <?php foreach ($displayBooks as $book): ?>

                        <tr>

                            <td><?= htmlspecialchars($book["id"]) ?></td>

                            <td><?= htmlspecialchars($book["title"]) ?></td>

                            <td><?= htmlspecialchars($book["author"]) ?></td>

                            <td><?= htmlspecialchars($book["genre"]) ?></td>

                            <td><?= htmlspecialchars($book["year"]) ?></td>

                            <td><?= htmlspecialchars($book["pages"]) ?></td>

                            <td>

                                <!-- Edit -->
                                <a
                                    href="index.php?edit_id=<?= htmlspecialchars($book["id"]) ?>"
                                    class="btn btn-warning btn-sm"
                                >
                                    Edit
                                </a>

                                <!-- Delete -->
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm"

                                    data-bs-toggle="modal"

                                    data-bs-target="#deleteModal<?= htmlspecialchars($book["id"]) ?>"
                                >
                                    Delete
                                </button>

                                <!-- Modal -->
                                <div
                                    class="modal fade"

                                    id="deleteModal<?= htmlspecialchars($book["id"]) ?>"

                                    tabindex="-1"
                                >

                                    <div class="modal-dialog">

                                        <div class="modal-content">

                                            <div class="modal-header">

                                                <h5 class="modal-title">
                                                    Confirm Delete
                                                </h5>

                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal"
                                                ></button>

                                            </div>

                                            <div class="modal-body">

                                                Are you sure you want to delete this book?

                                            </div>

                                            <div class="modal-footer">

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal"
                                                >
                                                    Cancel
                                                </button>

                                                <form method="POST" action="index.php">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="book_id"
                                                        value="<?= htmlspecialchars($book["id"]) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>