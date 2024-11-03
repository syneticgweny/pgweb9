<?php
// Sesuaikan dengan setting MySQL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pgweb_acara8";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Proses input data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Jika ID ada, maka melakukan update data
        $stmt = $conn->prepare("UPDATE sleman SET Kecamatan = ?, Longitude = ?, Latitude = ?, Luas = ?, Jumlah_penduduk = ? WHERE id = ?");
        $stmt->bind_param(
            "sddddi",
            $_POST['kecamatan'],
            $_POST['longitude'],
            $_POST['latitude'],
            $_POST['luas'],
            $_POST['jumlah_penduduk'],
            $_POST['id']
        );
    } else {
        // Jika ID tidak ada, maka melakukan insert data baru
        $stmt = $conn->prepare("INSERT INTO sleman (Kecamatan, Longitude, Latitude, Luas, Jumlah_penduduk) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sdddd",
            $_POST['kecamatan'],
            $_POST['longitude'],
            $_POST['latitude'],
            $_POST['luas'],
            $_POST['jumlah_penduduk']
        );
    }

    if ($stmt->execute()) {
        echo "<p style='text-align:center;color:green;'>Data berhasil disimpan</p>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p style='text-align:center;color:red;'>Gagal menyimpan data: " . $stmt->error . "</p>";
    }
    $stmt->close();
}


// Mengecek apakah ada permintaan hapus data
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM sleman WHERE id = ?";

    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<p style='text-align:center;color:green;'>Data berhasil dihapus</p>";
    } else {
        echo "<p style='text-align:center;color:red;'>Gagal menghapus data</p>";
    }
    $stmt->close();
}

// Menampilkan data dari tabel
$sql = "SELECT * FROM sleman";
$result = $conn->query($sql);

// Menyiapkan array untuk data marker
$markers = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $markers[] = array(
            'kecamatan' => $row['Kecamatan'],
            'lat' => $row['Latitude'],
            'lng' => $row['Longitude'],
            'penduduk' => $row['Jumlah_penduduk'],
            'luas' => $row['Luas']
        );
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Data Penduduk Kecamatan Sleman</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        body {
            background-color: #FFD1DC;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        #map {
            height: 500px;
            width: 80%;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .input-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 300px;
        }

        .form-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #FF69B4;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            background-color: #FF69B4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .btn:hover {
            background-color: #FF85B2;
        }

        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
            background-color: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #FF69B4;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        h2 {
            text-align: center;
            color: #FF69B4;
        }

        .delete-btn {
            background-color: #FF69B4;
            color: white;
            padding: 9px 10px;
            text-decoration: none;
            border-radius: 5px;
        }

        .delete-btn:hover {
            background-color: #FF85B2;
        }

        .instructions {
            text-align: center;
            color: #FF69B4;
            margin: 10px 0;
            font-style: italic;
        }
    </style>
</head>

<body>
    <h2>Data Penduduk Kecamatan Sleman</h2>

    <p class="instructions">Klik pada peta untuk menambahkan data baru atau klik tombol Edit di tabel untuk mengedit data.</p>

    <!-- Form Input Data -->
    <div class="form-overlay" id="overlay"></div>
    <div class="input-form" id="inputForm">
        <form method="POST" action="">
            <input type="hidden" id="id" name="id">
            <div class="form-group">
                <label for="kecamatan">Nama Kecamatan:</label>
                <input type="text" id="kecamatan" name="kecamatan" required>
            </div>
            <div class="form-group">
                <label for="latitude">Latitude:</label>
                <input type="number" id="latitude" name="latitude" step="any" required readonly>
            </div>
            <div class="form-group">
                <label for="longitude">Longitude:</label>
                <input type="number" id="longitude" name="longitude" step="any" required readonly>
            </div>
            <div class="form-group">
                <label for="luas">Luas (km²):</label>
                <input type="number" id="luas" name="luas" step="any" required>
            </div>
            <div class="form-group">
                <label for="jumlah_penduduk">Jumlah Penduduk:</label>
                <input type="number" id="jumlah_penduduk" name="jumlah_penduduk" required>
            </div>
            <div style="text-align: center;">
                <button type="submit" name="submit" class="btn">Simpan</button>
                <button type="button" class="btn" onclick="hideForm()">Batal</button>
            </div>
        </form>
    </div>

    <!-- Div untuk peta -->
    <div id="map"></div>

    <!-- Tabel data -->
    <?php
    if ($result->num_rows > 0) {
        echo "<table><tr>
            <th>Kecamatan</th>
            <th>Longitude</th>
            <th>Latitude</th>
            <th>Luas</th>
            <th>Jumlah Penduduk</th>
            <th>Aksi</th></tr>";

        $result->data_seek(0);

        while ($row = $result->fetch_assoc()) {
            echo "<tr><td align='left'>" . htmlspecialchars($row["Kecamatan"]) . "</td><td>" .
                htmlspecialchars($row["Longitude"]) . "</td><td>" .
                htmlspecialchars($row["Latitude"]) . "</td><td>" .
                htmlspecialchars($row["Luas"]) . "</td><td>" .
                htmlspecialchars($row["Jumlah_penduduk"]) . "</td>
                <td>
                    <button class='btn' onclick='editData(" . json_encode($row) . ")'>Edit</button>
                    <a class='delete-btn' href='index.php?delete_id=" . $row["id"] . "' onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\")'>Delete</a>
                </td></tr>";
                    
        }
        echo "</table>";
    } else {
        echo "<p style='text-align:center;'>0 results</p>";
    }

    $conn->close();
    ?>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Inisialisasi peta
        var map = L.map('map').setView([-7.716, 110.35], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Menambahkan marker untuk data yang ada
        var markers = <?php echo json_encode($markers); ?>;

        markers.forEach(function(marker) {
            L.marker([parseFloat(marker.lat), parseFloat(marker.lng)])
                .bindPopup(
                    "<strong>" + marker.kecamatan + "</strong><br>" +
                    "Jumlah Penduduk: " + marker.penduduk + "<br>" +
                    "Luas: " + marker.luas
                )
                .addTo(map);
        });

        var tempMarker = null;

        map.on('click', function(e) {
            if (tempMarker) {
                map.removeLayer(tempMarker);
            }

            tempMarker = L.marker(e.latlng).addTo(map);

            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
            document.getElementById('id').value = "";
            showForm();
        });

        function showForm() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('inputForm').style.display = 'block';
        }

        function hideForm() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('inputForm').style.display = 'none';
            if (tempMarker) {
                map.removeLayer(tempMarker);
            }
        }

        function editData(data) {
            document.getElementById('id').value = data.id;
            document.getElementById('kecamatan').value = data.Kecamatan;
            document.getElementById('latitude').value = data.Latitude;
            document.getElementById('longitude').value = data.Longitude;
            document.getElementById('luas').value = data.Luas;
            document.getElementById('jumlah_penduduk').value = data.Jumlah_penduduk;
            showForm();
        }
    </script>
</body>

</html>