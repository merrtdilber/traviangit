<?php
// Hata ayıklama
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Harici CSS dosyası
?>
<link rel="stylesheet" href="assets/css/serverlist.css">

<?php
// Ana database bağlantısı (serverlist çekmek için)
$main_db = mysqli_connect("localhost", "root", "", "travianmaindata");
if (!$main_db) {
    die('Main DB bağlantı hatası: ' . mysqli_connect_error());
}

// Server listesi çekiliyor
$result = mysqli_query($main_db, "SELECT * FROM serverlist ORDER BY start_date ASC");
if (!$result) {
    error_log('Serverlist sorgu hatası: ' . mysqli_error($main_db));
    die('Sunucu listesi alınamadı.');
}
if (mysqli_num_rows($result) === 0) {
    echo '<p>Henüz sunucu yok.</p>';
    return;
}
?>

<div class="Modal playNowModal overlay" id="overlay" role="none" style="display:none">
    <div class="mobileCloseButton" role="none">
        <!-- Kapat butonu -->
    </div>
    <div id="Login" class="popupBox">
        <div class="box default">
            <div class="boxTitle"><h1>Oynamak için sunucu seç!</h1></div>
            <div class="content"><div class="boxBody"><div class="worldSelection shown"><div class="transformWrapper"><div class="worldGroup">

<?php while ($server = mysqli_fetch_assoc($result)): ?>
    <?php
    // Tarih ve durum kontrolü
    $serverStart  = strtotime($server['start_date']);
    $serverAgeDays = floor((time() - $serverStart) / 86400);
    $isActive     = ($server['status'] === 'active' && $serverStart <= time());

    // Her sunucu için kendi DB bağlantısı ve istatistikler
    $dbNameSafe = mysqli_real_escape_string($main_db, $server['db_name']);
    $server_db  = mysqli_connect("localhost", "root", "", $dbNameSafe);
    if (!$server_db) {
        error_log('Server DB bağlantı hatası: ' . mysqli_connect_error());
        continue;
    }

    // Toplam kullanıcı
    $q_total = mysqli_query($server_db, "SELECT COUNT(id) AS total FROM users");
    if (!$q_total) error_log('Toplam kullanıcı sorgu hatası: ' . mysqli_error($server_db));
    $count_users = $q_total ? mysqli_fetch_assoc($q_total)['total'] : 0;

    // Online kullanıcı
    $q_online = mysqli_query($server_db, "SELECT COUNT(id) AS online_total FROM users WHERE TIMESTAMPDIFF(SECOND,last_active,NOW())<=300");
    if (!$q_online) error_log('Online kullanıcı sorgu hatası: ' . mysqli_error($server_db));
    $online_users = $q_online ? mysqli_fetch_assoc($q_online)['online_total'] : 0;

    // En iyi oyuncular
    $q_top = mysqli_query($server_db, "SELECT username, cp FROM users ORDER BY cp DESC LIMIT 3");
    if (!$q_top) error_log('Top oyuncu sorgu hatası: ' . mysqli_error($server_db));
    $top_players = $q_top ? mysqli_fetch_all($q_top, MYSQLI_ASSOC) : [];

    mysqli_close($server_db);

    // Doluluk oranı ve renk
    $fill_percent = $server['players_limit'] ? round($count_users / $server['players_limit'] * 100) : 0;
    $bar_color    = $fill_percent < 50 ? '#00FF00' : ($fill_percent < 80 ? '#FFFF00' : '#FF0000');

    // Rozet
    if ($server['status'] === 'finished') {
        $badge      = 'Tamamlandı';
        $badgeColor = '#9E9E9E';
    } elseif ($serverStart > time()) {
        $badge      = 'Yakında';
        $badgeColor = '#FFC107';
    } elseif ($serverAgeDays <= 4) {
        $badge      = 'Yeni';
        $badgeColor = '#2196F3';
    } else {
        $badge      = 'Aktif';
        $badgeColor = '#4CAF50';
    }
    $fullBadge   = $fill_percent >= 80 ? '<div class="badgeFull">Son Yerler!</div>' : '';
    $achievement = '';
    if ($server['players_limit'] >= 5000) {
        $achievement = '<div class="achievement">En Büyük Sunucu</div>';
    } elseif ($count_users >= 1000) {
        $achievement = '<div class="achievement">Popüler Sunucu</div>';
    }

    // Arka plan resmi
    $bgImage = htmlspecialchars($server['background_image'] ?: 'background.jpg');
    $folderSafe = urlencode($server['folder']);
?>
    <div class="world default" style="background-image: url('images/<?= $bgImage ?>');" onclick="location.href='<?= htmlspecialchars($folderSafe) ?>/login.php';">
        <?= $fullBadge . $achievement ?>
        <div class="badge" style="background: <?= $badgeColor ?>;"><?= $badge ?></div>
        <h2><?= htmlspecialchars($server['server_name']) ?> ×X<?= (int) $server['speed'] ?></h2>
        <p>Toplam Oyuncular: <strong><?= $count_users ?></strong> | Online: <strong><?= $online_users ?></strong></p>
        <div class="progressBar"><div style="width: <?= $fill_percent ?>%; background: <?= $bar_color ?>;"></div></div>
        <p>Doluluk: <?= $fill_percent ?>%</p>

        <?php if ($isActive && $top_players): ?>
        <div class="topPlayers">
            <p>En Yüksek Kültür Puanlı Oyuncular:</p>
            <ul>
                <?php foreach ($top_players as $player): ?>
                    <li class="playerStat">
                        <span class="playerName"><?= htmlspecialchars($player['username']) ?></span>
                        <span class="playerCp"><?= number_format($player['cp']) ?> cp</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($folderSafe) ?>/login.php" class="joinBtn">Şimdi Katıl</a>
    </div>
<?php endwhile; ?>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<style>
.badgeFull { position:absolute; top:10px; left:10px; background:#F44336; color:#fff; padding:4px 8px; font-size:11px; border-radius:6px; z-index:2; }
.achievement { position:absolute; bottom:10px; left:10px; background:#673AB7; color:#fff; padding:4px 8px; font-size:10px; border-radius:6px; z-index:2; }
.badge { position:absolute; top:10px; right:10px; color:#fff; padding:4px 8px; font-size:11px; border-radius:6px; z-index:2; }
.world.default { animation:fadeInUp 0.6s ease forwards; cursor:pointer; padding:20px; min-height:400px; width:250px; border-radius:12px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.3); transition:0.3s; background-size:cover; background-position:center; position:relative; }
.world.default:hover { transform:scale(1.02); box-shadow:0 6px 12px rgba(0,0,0,0.5); }
.progressBar { background:#444; width:100%; height:10px; border-radius:5px; margin:10px 0; }
.progressBar div { height:100%; border-radius:5px; transition:width 1.5s ease-in-out; }
.topPlayers {
    margin-top: 10px;
    background: rgba(0, 0, 0, 0.4); /* Arkası yarı transparan kare */
    padding: 10px; /* İçerik çevresinde boşluk */
    border-radius: 8px; /* Köşeleri yuvarlak */
}
.topPlayers ul { list-style:none; padding:0; margin:5px 0; }
.topPlayers .playerStat {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:rgba(255,255,255,0.1);
    border-left:4px solid #FFD700;
    margin:4px 0;
    padding:6px 8px;
    border-radius:6px;
}
.topPlayers .playerStat:hover {
    background: rgba(255,215,0,0.2);
    padding: 8px 12px; /* Hover genişliği artırıldı */
}
.topPlayers .playerName { font-weight:600; color:#FFD700; font-size:10px; }
.topPlayers .playerCp { background:rgba(0,0,0,0.6); color:#fff; padding:2px 12px; border-radius:4px; font-size:10px; }
.joinBtn { display:inline-block; margin-top:10px; padding:8px 16px; background:#28a745; color:#fff; border:none; border-radius:6px; text-decoration:none; text-align:center; }
.joinBtn:hover { background:#218838; }
</style>
