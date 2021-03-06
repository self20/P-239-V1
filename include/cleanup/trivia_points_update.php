<?php
/**
 * \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/
 */
function cleanup_log($data)
{
    $text = sqlesc($data['clean_title']);
    $added = TIME_NOW;
    $ip = sqlesc($_SERVER['REMOTE_ADDR']);
    $desc = sqlesc($data['clean_desc']);
    sql_query("INSERT INTO cleanup_log (clog_event, clog_time, clog_ip, clog_desc) VALUES ($text, $added, $ip, {$desc})") or sqlerr(__FILE__, __LINE__);
}

function docleanup($data)
{
    global $INSTALLER09, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(1);
    $count = 0;

    $msgs_buffer = $users_buffer = $users = [];
    $i = 1;
    $sql = 'SELECT t.user_id, COUNT(t.correct) AS correct, u.username, u.modcomment FROM triviausers AS t INNER JOIN users AS u ON u.id = t.user_id WHERE t.correct=1 GROUP BY t.user_id ORDER BY COUNT(t.correct) DESC LIMIT 10';
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) > 0) {
        $subject = 'Trivia Bonus Points Award.';
        while ($winners = mysqli_fetch_assoc($res)) {
            extract($winners);
            switch ($i) {
                case 1:
                    $points = 10 * $correct;
                    break;
                case 2:
                    $points = 9 * $correct;
                    break;
                case 3:
                    $points = 8 * $correct;
                    break;
                case 4:
                    $points = 7 * $correct;
                    break;
                case 5:
                    $points = 6 * $correct;
                    break;
                case 6:
                    $points = 5 * $correct;
                    break;
                case 7:
                    $points = 4 * $correct;
                    break;
                case 8:
                    $points = 3 * $correct;
                    break;
                case 9:
                    $points = 2 * $correct;
                    break;
                case 10:
                    $points = 1 * $correct;
                    break;
            }

            $msg = 'You answered ' . number_format($correct) . " trivia question correctly and were awarded $points Bonus Points!!\n";
            $modcomment = $modcomment;
            $modcomment = get_date(TIME_NOW, 'DATE', 1) . " - Awarded Bonus Points for Trivia.\n" . $modcomment;
            $msgs_buffer[] = '(0,' . sqlesc($user_id) . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
            $users[] = $user_id;
            $mc1->begin_transaction('user_stats' . $user_id);
            $mc1->update_row(false, [
                'modcomment' => $modcomment,
            ]);
            $mc1->commit_transaction($INSTALLER09['expires']['user_stats']);
            sql_query('UPDATE users SET modcomment = ' . sqlesc($modcomment) . ", seedbonus = seedbonus + $points WHERE id = " . sqlesc($user_id)) or sqlerr(__FILE__, __LINE__);
            $count = $i++;
        }
    }

    if (!empty($msgs_buffer)) {
        sql_query('INSERT INTO messages (sender,receiver,added,msg,subject) VALUES ' . implode(', ', $msgs_buffer)) or sqlerr(__FILE__, __LINE__);
    }
    write_log('Cleanup - Trivia Bonus Points awarded to - ' . $count . ' Member(s)');
    foreach ($users as $user_id) {
        $mc1->delete_value('inbox_new_' . $user_id);
        $mc1->delete_value('inbox_new_sb_' . $user_id);
        $mc1->delete_value('userstats_' . $user_id);
        $mc1->delete_value('user_stats_' . $user_id);
        $mc1->delete_value('MyUser_' . $user_id);
        $mc1->delete_value('user' . $user_id);
    }

    sql_query('UPDATE triviaq SET asked = 0, current = 0') or sqlerr(__FILE__, __LINE__);
    $correct = get_row_count('triviausers', 'WHERE correct = 1');
    $incorrect = get_row_count('triviausers', 'WHERE correct = 0');
    sql_query('UPDATE triviasettings SET gameon = 0, finished = NOW(), correct = ' . sqlesc($correct) . ', incorrect = ' . sqlesc($incorrect) . ' WHERE gameon = 1') or sqlerr(__FILE__, __LINE__);
    sql_query('INSERT INTO triviasettings (gameon, started) VALUES (1, NOW())') or sqlerr(__FILE__, __LINE__);
    sql_query('TRUNCATE TABLE triviausers') or sqlerr(__FILE__, __LINE__);

    if ($queries > 0) {
        write_log("Updated Trivia Questions Clean -------------------- Trivia Questions cleanup Complete using $queries queries --------------------");
    }
    if (false !== mysqli_affected_rows($GLOBALS['___mysqli_ston'])) {
        $data['clean_desc'] = mysqli_affected_rows($GLOBALS['___mysqli_ston']) . ' items deleted/updated';
    }
    if ($data['clean_log']) {
        cleanup_log($data);
    }
}
