<?php
// Ana database bağlantısı (serverlist çekmek için)
$main_db = mysqli_connect("localhost", "root", "", "travianmaindata");
if (!$main_db) {
    die('Main DB bağlantı hatası: ' . mysqli_connect_error());
}
// Server listesi çekiyoruz
$servers = $main_db->query("SELECT * FROM serverlist ORDER BY start_date ASC");
?>

<div class="Modal playNowModal overlay" id="overlay" role="none" style="display:none">
    <div class="mobileCloseButton" role="none">
        <svg class="modalClose" viewBox="-6 -6 20 20">
            <path d="M2,18.4c6-12.3,14.4-18,14.4-18" transform="scale(.5)"></path>
            <path d="M0.2,2.2C8.8,7,16.1,16.7,16.1,16.7" transform="scale(.5)"></path>
        </svg>
    </div>

    <div id="Login" class="popupBox">
        <div class="box default">
            <div class="boxTitle">
                <h1><span>Oynamak için sunucu seç!</span></h1>
            </div>

            <div class="content">
                <div class="boxBody">
                    <div class="worldSelection shown">
                        <div class="transformWrapper">
                            <div class="worldGroup">

<?php
while ($server = mysqli_fetch_assoc($servers)) {
    $serverStart   = strtotime($server['start_date']);
    $serverAgeDays = floor((time() - $serverStart) / 86400);

    // Her sunucunun kendi database'ine bağlanıyoruz
    $db_name   = preg_replace('/[^a-zA-Z0-9_]/', '', $server['db_name']);
    $server_db = mysqli_connect("localhost", "root", "", $db_name);

    $count_users   = 0;
    $online_users  = 0;
    if ($server_db) {
        // Toplam kullanıcı sayısı
        $q_total = mysqli_query($server_db, "SELECT COUNT(id) AS total FROM users");
        if ($q_total) {
            $r_total     = mysqli_fetch_assoc($q_total);
            $count_users = $r_total['total'];
        }
                // Online kullanıcı sayısı (son 5 dakika aktif olarak kabul ediliyor)
        // TIMESTAMPDIFF kullanarak kesin süre kontrolü
        $q_online = mysqli_query($server_db, "SELECT COUNT(id) AS online_total FROM users WHERE TIMESTAMPDIFF(SECOND, last_active, NOW()) <= 300");
        if ($q_online) {
            $r_online    = mysqli_fetch_assoc($q_online);
            $online_users = $r_online['online_total'];
        }
        mysqli_close($server_db);
    }

    // Doluluk oranı
    $fill_percent = $server['players_limit'] > 0 ? round(($count_users / $server['players_limit']) * 100) : 0;

    // Doluluk bar rengi
    if ($fill_percent < 50) {
        $bar_color = '#00FF00';
    } elseif ($fill_percent < 80) {
        $bar_color = '#FFFF00';
    } else {
        $bar_color = '#FF0000';
    }

    // Rozet ve renk
    $badge      = '';
    $badgeColor = '#FF5722';
    if ($server['status'] == 'finished') {
        $badge      = 'Tamamlandı';
        $badgeColor = '#9E9E9E';
    } elseif ($serverStart > time()) {
        $badge      = 'Yakında';
        $badgeColor = '#FFC107';
    } else {
        $daysSince = floor((time() - $serverStart) / 86400);
        if ($daysSince <= 4) {
            $badge      = 'Yeni';
            $badgeColor = '#2196F3';
        } else {
            $badge      = 'Aktif';
            $badgeColor = '#4CAF50';
        }
    }

    // Özel rozeti (doluluk)
    $fullBadge = $fill_percent >= 80
        ? '<div class="badgeFull">Son Yerler!</div>'
        : '';

    // Başarı rozeti
    $achievement = '';
    if ($server['players_limit'] >= 5000) {
        $achievement = '<div class="achievement">En Büyük Sunucu</div>';
    } elseif ($count_users >= 1000) {
        $achievement = '<div class="achievement">Popüler Sunucu</div>';
    }

    // Geri sayım metni
    $countdownText = '';
    if ($serverStart > time()) {
        $diff      = $serverStart - time();
        $d         = floor($diff / 86400);
        $h         = floor(($diff % 86400) / 3600);
        $m         = floor(($diff % 3600) / 60);
        $countdownText = "{$d} gün {$h} saat {$m} dakika kaldı";
    }
    $background = !empty($server['background_image']) ? htmlspecialchars($server['background_image']) : 'background.jpg';
?>
                                <div class="world default" role="none" onclick="location.href='<?php echo htmlspecialchars($server['folder']); ?>/login.php';">
                                    <?php echo $fullBadge; ?>
                                    <?php echo $achievement; ?>

                                    <div class="badge" style="background:<?php echo $badgeColor; ?>;"><?php echo $badge; ?></div>

                                    <h2><?php echo htmlspecialchars($server['server_name']); ?> ×X<?php echo (int)$server['speed']; ?></h2>
                                    <p>Toplam Oyuncular: <strong><?php echo $count_users; ?></strong></p>
                                    <p>Online Oyuncular: <strong><?php echo $online_users; ?></strong></p>

                                    <div class="progressBar">
                                        <div style="width:<?php echo $fill_percent; ?>%; background:<?php echo $bar_color; ?>;"></div>
                                    </div>
                                    <p>Doluluk: <?php echo $fill_percent; ?>%</p>

                                    <?php if (!empty($server['small_description'])): ?>
                                        <p class="smallDesc"><?php echo htmlspecialchars($server['small_description']); ?></p>
                                    <?php endif; ?>

                                    <div class="serverTime">
                                        <?php
echo !empty($countdownText)
    ? $countdownText
    : ($server['status'] == 'active' ? "{$serverAgeDays} gün önce başladı" : 'Sunucu tamamlandı');
?>
                                    </div>

                                    <button class="joinBtn">Şimdi Katıl</button>
                                </div>
<?php } // while bitiş
?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.world.default {
    animation: fadeInUp 0.6s ease forwards;
    cursor: pointer;
    padding: 20px;
    min-height: 400px;
    width: 250px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}
.world.default:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 12px rgba(0,0,0,0.5);
}
.badgeFull {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #F44336;
    color: #fff;
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 6px;
}
.achievement {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: #3F51B5;
    color: #fff;
    padding: 4px 8px;
    font-size: 10px;
    border-radius: 6px;
}
.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    color: #fff;
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 6px;
}
.progressBar {
    background: #444;
    width: 100%;
    height: 10px;
    border-radius: 5px;
    margin-top: 10px;
}
.progressBar div {
    height: 100%;
    border-radius: 5px;
    transition: width 1.5s ease-in-out;
}
.smallDesc {
    font-size: 11px;
    color: #FFD700;
    margin-top: 5px;
}
.serverTime {
    font-size: 12px;
    color: #eee;
    margin-top: 5px;
    display: flex;
    align-items: center;
}
.joinBtn {
    padding: 8px 16px;
    background: #28a745;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: 0.3s;
}
.joinBtn:hover {
    background: #218838;
}
@media (max-width: 768px) {
    .worldGroup {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .world.default {
        width: 90%;
    }
}
</style>
