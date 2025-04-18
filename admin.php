    <?php
    $conn = new mysqli("localhost", "root", "", "parth");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $query_result = null;
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_query'])) {
        $sql = $_POST['custom_query'];
        $query_result = $conn->query($sql);
    }

    $relatedTables = ['userdiet', 'workoutplanner', 'usersubscription'];

    // Handle deletion request
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_email'])) {
        $user_email = $conn->real_escape_string($_POST['delete_user_email']);
        
        foreach ($relatedTables as $table) {
            $conn->query("DELETE FROM `$table` WHERE user_email = '$user_email'");
        }

        $conn->query("DELETE FROM `userstable` WHERE user_email = '$user_email'");
    }

    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) $tables[] = $row[0];
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Panel</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            /* Your existing styles (no change needed) */
            .admin-section { padding: 40px; }
            .admin-section h2 { text-align: center; margin-bottom: 30px; }
            .table-wrapper { overflow-x: auto; margin-bottom: 50px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            table { width: 100%; min-width: 800px; border-collapse: collapse; }
            th, td { padding: 12px; border: 1px solid #ccc; background-color: #fdfdfd; white-space: nowrap; }
            th { background-color: #222; color: yellow; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .table-title { margin-top: 50px; font-size: 24px; font-weight: bold; color: #333; }
            .delete-btn { background: red; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
            .delete-btn:hover { background-color: darkred; }
            .table-toggle { background-color: black; color: white; padding: 12px; margin: 10px 0; border: none; width: 100%; text-align: left; font-size: 16px; cursor: pointer; border-radius: 5px; }
            .arrow { float: right; font-size: 18px; }
            .table-container { display: none; margin-bottom: 30px; overflow-x: auto; }
            form { margin: 0; }
            input[type="submit"] { background-color: #dc3545; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        </style>
        <script>
            function toggleTable(index) {
                const tableContainer = document.getElementById('table-' + index);
                const arrow = document.getElementById('arrow-' + index);
                if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
                    tableContainer.style.display = 'block';
                    arrow.textContent = '▲';
                } else {
                    tableContainer.style.display = 'none';
                    arrow.textContent = '▼';
                }
            }
        </script>
    </head>
    <body>

    <header>
        <img src="assets/logo.png" alt="Logo" class="logo">
        <div class="nav">
            <button onclick="window.location.href='index.php'">Home</button>
            <button onclick="window.location.href='dietpage.php'">Diet</button>
            <button onclick="window.location.href='logout.php'" class="login-btn">Logout</button>
        </div>
    </header>

    <div class="admin-section">
    <h1>Welcome Admin</h1>

    <form method="post">
        <label>Run SQL Query:</label><br>
        <textarea name="custom_query" rows="4" cols="50"></textarea><br>
        <input type="submit" value="Execute">
    </form>

    <?php if ($query_result && $query_result instanceof mysqli_result): ?>
        <h2>Query Result:</h2>
        <table>
            <tr>
                <?php while ($field = $query_result->fetch_field()): ?>
                    <th><?= htmlspecialchars($field->name) ?></th>
                <?php endwhile; ?>
            </tr>
            <?php while ($row = $query_result->fetch_assoc()): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td><?= htmlspecialchars($value) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php elseif ($query_result === true): ?>
        <p>Query executed successfully.</p>
    <?php elseif ($query_result): ?>
        <p>Error: <?= $conn->error ?></p>
    <?php endif; ?>

    <?php foreach ($tables as $index => $table): ?>
        <button class="table-toggle" onclick="toggleTable(<?= $index ?>)">
            <?= htmlspecialchars($table) ?>
            <span class="arrow" id="arrow-<?= $index ?>">▼</span>
        </button>
        <div class="table-container" id="table-<?= $index ?>">
            <table>
                <thead>
                    <tr>
                        <?php
                        $cols = $conn->query("SHOW COLUMNS FROM `$table`");
                        while ($col = $cols->fetch_assoc()) {
                            echo "<th>" . htmlspecialchars($col['Field']) . "</th>";
                        }
                        if ($table === "userstable") echo "<th>Action</th>";
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = $conn->query("SELECT * FROM `$table`");
                    while ($row = $rows->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row as $val) {
                            echo "<td>" . htmlspecialchars($val) . "</td>";
                        }
                        if ($table === "userstable" && isset($row['user_email'])) {
                            echo "<td>
                                <form method='post'>
                                    <input type='hidden' name='delete_user_email' value='" . htmlspecialchars($row['user_email']) . "'>
                                    <input type='submit' class='delete-btn' value='Delete'>
                                </form>
                            </td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    </body>
    </html>
