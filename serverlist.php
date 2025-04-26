<div class="Modal playNowModal overlay" id="overlay" role="none" style="display:none">
    <div class="mobileCloseButton" role="none">
        <svg class="modalClose" viewbox="-6 -6 20 20">
            <path d="M2,18.4c6-12.3,14.4-18,14.4-18" transform="scale(.5)"></path>
            <path d="M0.2,2.2C8.8,7,16.1,16.7,16.1,16.7" transform="scale(.5)"></path>
        </svg>
    </div>

    <div id="Login" class="popupBox">
        <div class="box default">
            <div class="boxTitle">
                <h1><span>Oynamak için bir sunucu seç!</span></h1>
            </div>

            <div class="content">
                <div class="boxBody">
                    <div class="worldSelection shown">
                        <div class="transformWrapper">
                            <div class="worldGroup">

<?php
// Veritabanı bağlantısı
$db = mysqli_connect("localhost", "root", "", "travian");

if (!$db) {
    die("Veritabanı bağlantı hatası: " . mysqli_connect_error());
}

// Toplam oyuncu sayısı (id > 4 olanlar)
$count_users_query = $db->query("SELECT COUNT(*) AS total FROM `users` WHERE `id` > 4");
$count_users_row = mysqli_fetch_assoc($count_users_query);
$count_users = $count_users_row['total'] ?? 0;

// Online oyuncu sayısı (son 1 saat içinde aktif olanlar)
$one_hour_ago = time() - (60 * 60);
$count_online_query = $db->query("SELECT COUNT(*) AS online FROM `users` WHERE `timestamp` > $one_hour_ago AND tribe NOT IN (0,5)");
$count_online_row = mysqli_fetch_assoc($count_online_query);
$count_online = $count_online_row['online'] ?? 0;

// Sunucu başlangıç tarihi
$server_start_time = strtotime('12.09.2020 00:00:00');

// Eğer sunucu henüz başlamadıysa online sayısı 0
if ($server_start_time > time()) {
    $count_online = 0;
}

// Sunucunun açık olduğu gün sayısı
$days_since_start = round((time() - $server_start_time) / 86400);

// Sunucu kazanım (bitirme) kontrolü
$config_query = $db->query("SELECT * FROM `config` LIMIT 1");
$config = mysqli_fetch_assoc($config_query);
$winmoment = isset($config['winmoment']) ? $config['winmoment'] : 0;
?>

                                <div class="world default" role="none" data-url="games/login.php">
                                    <h2>Sunucu &times;X1,000</h2>
                                    <p>Toplam Oyuncu: <strong><font color="#00FF00"><?php echo $count_users; ?></font></strong> 
                                    Çevrimiçi Oyuncu: <strong><font color="#00FF00"><?php echo $count_online; ?></font></strong></p>
                                    <p class="spacer"></p>
                                    <div class="serverTime" title="Sunucu yaşı">
                                        <svg class="clock" viewBox="0 0 74 74">
                                            <circle cx="37" cy="37" r="33"></circle>
                                            <path d="M33.67 13v27.33h26"></path>
                                        </svg>
                                        <span>
                                            <?php
                                            if ($server_start_time <= time()) {
                                                if ($winmoment == 0) {
                                                    echo $days_since_start . ' gün önce başladı';
                                                } else {
                                                    echo 'Sunucu sona erdi';
                                                }
                                            } else {
                                                echo 'Başlangıç: ' . date("d.m.Y H:i:s", $server_start_time);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div> <!-- world default -->

                            </div> <!-- worldGroup -->
                        </div> <!-- transformWrapper -->
                    </div> <!-- worldSelection -->
                </div> <!-- boxBody -->
            </div> <!-- content -->
        </div> <!-- box default -->
    </div> <!-- Login -->
</div> <!-- Modal -->
