-- CBT MTsN 1 Mesuji - Database Schema
-- Created for CBT Application

CREATE DATABASE IF NOT EXISTS cbt_mtsn1mesuji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cbt_mtsn1mesuji;

CREATE TABLE admin (
  id VARCHAR(10) PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  nama VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password: 123456 hashed with bcrypt
INSERT INTO admin VALUES ('A1', 'admin@cbt.com', 'Admin Utama', '$2y$10$F5ylHOr0SWezwgKNv0H6d.DRQczCqCKZ70oY1sDM56TiGAcpR9ku6', NOW());

CREATE TABLE guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nik VARCHAR(16) UNIQUE NOT NULL,
  nama VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kelas (
  id VARCHAR(10) PRIMARY KEY,
  nama_kelas VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mapel (
  id VARCHAR(10) PRIMARY KEY,
  nama_mapel VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nisn VARCHAR(10) UNIQUE NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kelas_id VARCHAR(10),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

CREATE TABLE relasi_guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guru_id INT NOT NULL,
  kelas_id VARCHAR(10) NOT NULL,
  mapel_id VARCHAR(10) NOT NULL,
  FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
  FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
  FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE
);

CREATE TABLE bank_soal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guru_id INT,
  admin_id VARCHAR(10),
  nama_soal VARCHAR(200) NOT NULL,
  mapel_id VARCHAR(10),
  waktu_mengerjakan INT NOT NULL,
  bobot_pg DECIMAL(5,2) DEFAULT 0,
  bobot_esai DECIMAL(5,2) DEFAULT 0,
  bobot_menjodohkan DECIMAL(5,2) DEFAULT 0,
  bobot_benar_salah DECIMAL(5,2) DEFAULT 0,
  jumlah_soal INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE SET NULL
);

CREATE TABLE soal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bank_soal_id INT NOT NULL,
  nomor_soal INT NOT NULL,
  jenis_soal ENUM('pg','esai','menjodohkan','benar_salah') NOT NULL,
  pertanyaan TEXT NOT NULL,
  opsi_a TEXT,
  opsi_b TEXT,
  opsi_c TEXT,
  opsi_d TEXT,
  opsi_e TEXT,
  kunci_jawaban VARCHAR(10),
  pasangan_kiri TEXT,
  pasangan_kanan TEXT,
  pasangan_jawaban TEXT,
  jawaban_bs ENUM('benar','salah'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bank_soal_id) REFERENCES bank_soal(id) ON DELETE CASCADE
);

CREATE TABLE ruang_ujian (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_ruang VARCHAR(200) NOT NULL,
  token VARCHAR(10) UNIQUE NOT NULL,
  guru_id INT,
  admin_id VARCHAR(10),
  bank_soal_id INT NOT NULL,
  waktu_hentikan INT NOT NULL,
  batas_keluar INT DEFAULT 3,
  tanggal_mulai DATETIME NOT NULL,
  tanggal_selesai DATETIME NOT NULL,
  acak_soal TINYINT(1) DEFAULT 0,
  acak_jawaban TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bank_soal_id) REFERENCES bank_soal(id)
);

CREATE TABLE ruang_ujian_kelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ruang_ujian_id INT NOT NULL,
  kelas_id VARCHAR(10) NOT NULL,
  FOREIGN KEY (ruang_ujian_id) REFERENCES ruang_ujian(id) ON DELETE CASCADE,
  FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

CREATE TABLE ujian_siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ruang_ujian_id INT NOT NULL,
  siswa_id INT NOT NULL,
  status ENUM('belum','sedang','selesai') DEFAULT 'belum',
  waktu_mulai DATETIME,
  waktu_selesai DATETIME,
  waktu_tambahan INT DEFAULT 0,
  jumlah_benar INT DEFAULT 0,
  jumlah_salah INT DEFAULT 0,
  nilai DECIMAL(5,2) DEFAULT 0,
  jumlah_keluar INT DEFAULT 0,
  acak_soal_order TEXT,
  acak_jawaban_order TEXT,
  FOREIGN KEY (ruang_ujian_id) REFERENCES ruang_ujian(id) ON DELETE CASCADE,
  FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
  UNIQUE KEY unique_ujian (ruang_ujian_id, siswa_id)
);

CREATE TABLE jawaban_siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ujian_siswa_id INT NOT NULL,
  soal_id INT NOT NULL,
  jawaban TEXT,
  is_ragu TINYINT(1) DEFAULT 0,
  is_benar TINYINT(1) DEFAULT 0,
  answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ujian_siswa_id) REFERENCES ujian_siswa(id) ON DELETE CASCADE,
  FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE,
  UNIQUE KEY unique_jawaban (ujian_siswa_id, soal_id)
);

CREATE TABLE pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200),
  isi TEXT NOT NULL,
  admin_id VARCHAR(10),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pengumuman_kelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pengumuman_id INT NOT NULL,
  kelas_id VARCHAR(10) NOT NULL,
  FOREIGN KEY (pengumuman_id) REFERENCES pengumuman(id) ON DELETE CASCADE
);

CREATE TABLE setting (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_setting VARCHAR(100) UNIQUE NOT NULL,
  nilai VARCHAR(255)
);

INSERT INTO setting (nama_setting, nilai) VALUES ('exambrowser_mode', '0');
