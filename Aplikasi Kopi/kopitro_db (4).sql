-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Agu 2026 pada 10.41
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kopitro_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `company_config`
--

CREATE TABLE `company_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nama_usaha` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `nama_pemilik` varchar(255) DEFAULT NULL,
  `harga_per_cup` int(11) DEFAULT 15000,
  `logo` varchar(255) DEFAULT NULL,
  `ttd` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `company_config`
--

INSERT INTO `company_config` (`id`, `nama_usaha`, `alamat`, `kota`, `telepon`, `nama_pemilik`, `harga_per_cup`, `logo`, `ttd`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, 15000, NULL, NULL, '2026-07-28 17:47:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `cuaca` tinyint(1) NOT NULL DEFAULT 0,
  `hari_gajian` tinyint(1) NOT NULL DEFAULT 0,
  `promosi` tinyint(1) NOT NULL DEFAULT 0,
  `persen_daring` decimal(5,2) NOT NULL DEFAULT 0.00,
  `jumlah_penjualan` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `tanggal`, `cuaca`, `hari_gajian`, `promosi`, `persen_daring`, `jumlah_penjualan`, `created_at`) VALUES
(15, '2026-07-27', 1, 0, 1, 0.45, 32, '2026-07-27 14:38:25'),
(16, '2026-07-27', 1, 0, 1, 0.45, 38, '2026-07-27 14:49:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `prediksi`
--

CREATE TABLE `prediksi` (
  `id_prediksi` int(11) NOT NULL,
  `tanggal_prediksi` date NOT NULL,
  `cuaca` enum('Cerah','Hujan') NOT NULL,
  `hari_gajian` enum('Ya','Tidak') NOT NULL,
  `promosi` enum('Ya','Tidak') NOT NULL,
  `persen_daring` int(11) NOT NULL,
  `hasil_prediksi` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `prediksi`
--

INSERT INTO `prediksi` (`id_prediksi`, `tanggal_prediksi`, `cuaca`, `hari_gajian`, `promosi`, `persen_daring`, `hasil_prediksi`, `created_at`) VALUES
(1, '2026-07-21', 'Cerah', '', 'Ya', 0, 35.06, '2026-07-21 07:31:33'),
(2, '2026-07-21', 'Cerah', '', 'Ya', 0, 35.06, '2026-07-21 08:37:58'),
(3, '2026-07-21', '', 'Ya', 'Ya', 1, 35.69, '2026-07-21 10:13:21'),
(4, '2026-07-22', '', '', 'Ya', 0, 31.07, '2026-07-22 08:12:23'),
(5, '2026-07-22', 'Cerah', '', '', 1, 21.03, '2026-07-22 14:01:23'),
(6, '2026-07-22', '', '', '', 1, 17.04, '2026-07-22 14:42:39'),
(7, '2026-07-22', 'Cerah', '', '', 0, 17.63, '2026-07-22 15:01:19'),
(8, '2026-07-22', '', '', '', 1, 17.04, '2026-07-22 16:37:36'),
(9, '2026-07-27', '', 'Ya', '', 0, 19.96, '2026-07-27 13:29:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin', 'admin123', 'admin', '2026-07-17 21:25:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `validasi_model`
--

CREATE TABLE `validasi_model` (
  `id_validasi` int(11) NOT NULL,
  `r2` decimal(5,4) DEFAULT NULL,
  `mae` decimal(10,4) DEFAULT NULL,
  `mape` decimal(10,4) DEFAULT NULL,
  `akurasi` decimal(5,2) DEFAULT NULL,
  `tanggal_validasi` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `validasi_model`
--

INSERT INTO `validasi_model` (`id_validasi`, `r2`, `mae`, `mape`, `akurasi`, `tanggal_validasi`, `created_at`) VALUES
(1, 0.0000, 0.8700, 2.2800, NULL, NULL, '2026-07-21 06:59:26'),
(2, 0.0000, 1.9400, 5.2400, NULL, NULL, '2026-07-21 07:40:17'),
(3, 0.0000, 3.9400, 10.1000, NULL, NULL, '2026-07-21 07:49:44'),
(4, 0.0000, 2.9400, 7.7400, NULL, NULL, '2026-07-21 08:23:53'),
(5, 0.0000, 2.9400, 7.7400, NULL, NULL, '2026-07-21 08:38:08'),
(6, 0.0000, 2.9400, 7.7400, NULL, NULL, '2026-07-21 09:03:13'),
(7, 0.0000, 2.9400, 7.7400, NULL, NULL, '2026-07-21 09:22:15'),
(8, 0.0000, 2.9000, 7.6300, NULL, NULL, '2026-07-22 08:08:32'),
(9, 0.0000, 2.9000, 7.6300, NULL, NULL, '2026-07-22 08:45:38'),
(10, 0.0000, 2.9000, 7.6300, NULL, NULL, '2026-07-22 08:58:05'),
(11, 0.0000, 6.0000, 40.0000, NULL, NULL, '2026-07-22 14:01:51'),
(12, 0.0000, 3.0000, 15.0000, NULL, NULL, '2026-07-22 14:42:56'),
(13, 0.0000, 3.6000, 25.7100, NULL, NULL, '2026-07-22 15:02:11'),
(14, 0.0000, 3.0000, 21.4300, NULL, NULL, '2026-07-22 16:38:27'),
(15, 0.0000, 5.0000, 20.0000, NULL, NULL, '2026-07-27 13:30:24'),
(16, NULL, 2.9000, 7.6300, NULL, NULL, '2026-07-27 14:25:52'),
(17, NULL, 3.1000, 9.6900, NULL, NULL, '2026-07-27 14:38:25'),
(18, NULL, 2.9000, 7.6300, NULL, NULL, '2026-07-27 14:49:36');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `company_config`
--
ALTER TABLE `company_config`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`);

--
-- Indeks untuk tabel `prediksi`
--
ALTER TABLE `prediksi`
  ADD PRIMARY KEY (`id_prediksi`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `validasi_model`
--
ALTER TABLE `validasi_model`
  ADD PRIMARY KEY (`id_validasi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `prediksi`
--
ALTER TABLE `prediksi`
  MODIFY `id_prediksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `validasi_model`
--
ALTER TABLE `validasi_model`
  MODIFY `id_validasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
