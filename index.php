<?php
include 'config.php';

$connect = mysqli_connect($db_congig['host'], $db_congig['username'], $db_congig['password'],
    $db_congig['db_name']);
if ($connect == false) {
    print("Помилка: Неможливо підключитись до MySQL ".mysqli_connect_error());

    die;
}

mysqli_set_charset($connect, "utf8");

// Set Date Language to Ukrainian
$sql = 'SET lc_time_names = "uk_UA"';
mysqli_query($connect, $sql);

// Task queries
$sql = [];
$sql[1] = 'SELECT
            SUM(`bonus_amount`) AS `bonus_sum`,
            MONTHNAME(`bonus_date`) AS `month_name`,
            YEAR(`bonus_date`) AS `year`
        FROM `bonus`
        GROUP BY YEAR(`bonus_date`), MONTH(`bonus_date`)
        ORDER BY `bonus_sum` DESC
        LIMIT 0, 1';

$sql[2] = 'SELECT
            `w`.`first_name`,
            `w`.`last_name`,
            `w`.`salary`,
            `t`.`worker_title`
        FROM `worker` AS `w`
        LEFT JOIN `title` AS `t` ON( `w`.`worker_id` = `t`.`worker_ref_id` )
        ORDER BY `w`.`first_name`, `w`.`last_name` ASC';

$sql[3] = 'SELECT
            `w`.`first_name`,
            `w`.`last_name`
        FROM `worker` AS `w`
        LEFT JOIN `bonus` AS `b` ON( `w`.`worker_id` = `b`.`worker_ref_id` )
        WHERE `b`.`bonus_id` IS NULL
        ORDER BY `w`.`first_name`, `w`.`last_name` ASC';

$sql[4] = 'SELECT
            `w`.`first_name`,
            `w`.`last_name`
        FROM `worker` AS `w`
        LEFT JOIN `title` AS `t` ON( `w`.`worker_id` = `t`.`worker_ref_id` )
        LEFT JOIN `bonus` AS `b` ON( `w`.`worker_id` = `b`.`worker_ref_id` AND `b`.`bonus_date` >= `t`.`affected_from` )
        WHERE `b`.`bonus_id` IS NOT NULL
        GROUP BY `w`.`worker_id`
        ORDER BY `w`.`first_name`, `w`.`last_name` ASC';

$sql[5] = 'SELECT
            `w`.`department`,
            SUM(`b`.`bonus_amount`) AS `bonus_sum`
        FROM `worker` AS `w`
        LEFT JOIN `bonus` AS `b` ON( `w`.`worker_id` = `b`.`worker_ref_id` )
        GROUP BY `w`.`department`';

// Function save mysql result to csv file
function db_result_to_csv($task, $connect, $sql)
{
    if (empty($sql[$task])) {
        print('Невірний ідентифікатор завдання');

        die;
    }

    $sql = $sql[$task];
    $result = mysqli_query($connect, $sql);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $separator = ',';

    $lines = [implode($separator, array_keys($rows[0]))];

    foreach ($rows as $row) {
        $row = array_map(
            function ($value) {
                $value = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $value);
                $value = str_replace('"', '', $value);

                return '"'.$value.'"';
            },
            $row
        );

        $lines[] = implode($separator, $row);
    }

    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename=doba_ua_task_'.$task.'.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo implode("\n", $lines);

    die;
}

if (isset($_GET['task'])) {
    db_result_to_csv($_GET['task'], $connect, $sql);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Doba.ua - Тестове завдання</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
          integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1>Тестове завдання для Doba.ua</h1>
    <?php
    // Task 1

    $result = mysqli_query($connect, $sql[1]);

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h2>Завдання 1.</h2>
            <p>Знайти місяць, за який видали найбільшу кількість бонусів</p>
            <h4>Запит</h4>
            <code><?php echo $sql[1]; ?></code>
            <h4>Результат</h4>
            <table class="table">
                <thead>
                <tr>
                    <th>Рік</th>
                    <th>Місяць</th>
                    <th>Бонус</th>
                </tr>
                </thead>
                <tr>
                    <td><?php echo $rows[0]['year']; ?></td>
                    <td><?php echo $rows[0]['month_name']; ?></td>
                    <td><?php echo $rows[0]['bonus_sum']; ?></td>
                </tr>
            </table>
            <div><a target="_blank" class="btn btn-primary" href="?task=1">Скачати CSV</a></div>
        </div>
    </div>
    <?php
    // Task 2

    $result = mysqli_query($connect, $sql[2]);

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h2>Завдання 2.</h2>
            <p>Вивести зарплату по тайтлах</p>
            <h4>Запит</h4>
            <code><?php echo $sql[2]; ?></code>
            <h4>Результат</h4>
            <?php
            if (empty($rows)) {
                ?>
                Немає резултатів
                <?php
            } else {
                ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Ім'я (посада)</th>
                        <th>Зарплата</th>
                    </tr>
                    </thead>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?php echo $row['first_name'].' '.$row['last_name'].' ('.$row['worker_title'].')'; ?></td>
                            <td><?php echo $row['salary']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php
            }
            ?>
            <div><a target="_blank" class="btn btn-primary" href="?task=2">Скачати CSV</a></div>
        </div>
    </div>
    <?php
    // Task 3

    $result = mysqli_query($connect, $sql[3]);

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h2>Завдання 3.</h2>
            <p>Знайти працівників, які не отримували бонуси</p>
            <h4>Запит</h4>
            <code><?php echo $sql[3]; ?></code>
            <h4>Результат</h4>
            <?php
            if (empty($rows)) {
                ?>
                Немає резултатів
                <?php
            } else {
                ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Ім'я</th>
                    </tr>
                    </thead>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?php echo $row['first_name'].' '.$row['last_name']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php
            }
            ?>
            <div><a target="_blank" class="btn btn-primary" href="?task=3">Скачати CSV</a></div>
        </div>
    </div>
    <?php
    // Task 4

    $result = mysqli_query($connect, $sql[4]);

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h2>Завдання 4.</h2>
            <p>Знайти працівників, що отримували бонус на поточній посаді</p>
            <h4>Запит</h4>
            <code><?php echo $sql[4]; ?></code>
            <h4>Результат</h4>
            <?php
            if (empty($rows)) {
                ?>
                Немає резултатів
                <?php
            } else {
                ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Ім'я</th>
                    </tr>
                    </thead>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?php echo $row['first_name'].' '.$row['last_name']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php
            }
            ?>
            <div><a target="_blank" class="btn btn-primary" href="?task=4">Скачати CSV</a></div>
        </div>
    </div>
    <?php
    // Task 5

    $result = mysqli_query($connect, $sql[5]);

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    ?>
    <div class="card mb-3">
        <div class="card-body">
            <h2>Завдання 5.</h2>
            <p>Вивести сумарну кількість бонусів по відділу</p>
            <h4>Запит</h4>
            <code><?php echo $sql[5]; ?></code>
            <h4>Результат</h4>
            <?php
            if (empty($rows)) {
                ?>
                Немає резултатів
                <?php
            } else {
                ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Відділ</th>
                        <th>Бонус (сумма)</th>
                    </tr>
                    </thead>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?php echo $row['department']; ?></td>
                            <td><?php echo empty($row['bonus_sum']) ? 0 : $row['bonus_sum']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php
            }
            ?>
            <div><a target="_blank" class="btn btn-primary" href="?task=5">Скачати CSV</a></div>
        </div>
    </div>
</div>
</body>
</html>
