-- ============================================================================
-- Skema Database SIAKAD (Sistem Informasi Manajemen Akademik dan Nilai Mahasiswa)
-- Database : siakad_db
-- Engine   : InnoDB
-- Charset  : utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS siakad_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siakad_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS krs;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS tahun_ajaran;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS dosen;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS prodi;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','dosen','mahasiswa') NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE prodi (
  id_prodi INT AUTO_INCREMENT PRIMARY KEY,
  kode_prodi VARCHAR(10) NOT NULL UNIQUE,
  nama_prodi VARCHAR(100) NOT NULL,
  jenjang VARCHAR(5) NOT NULL DEFAULT 'S1'
) ENGINE=InnoDB;

CREATE TABLE mahasiswa (
  id_mahasiswa INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL UNIQUE,
  id_prodi INT NOT NULL,
  nim VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  no_hp VARCHAR(20),
  alamat TEXT,
  angkatan YEAR NOT NULL,
  status ENUM('aktif','cuti','lulus','keluar') NOT NULL DEFAULT 'aktif',
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
) ENGINE=InnoDB;

CREATE TABLE dosen (
  id_dosen INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL UNIQUE,
  id_prodi INT NOT NULL,
  nidn VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  no_hp VARCHAR(20),
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
) ENGINE=InnoDB;

CREATE TABLE mata_kuliah (
  id_mk INT AUTO_INCREMENT PRIMARY KEY,
  id_prodi INT NOT NULL,
  kode_mk VARCHAR(15) NOT NULL UNIQUE,
  nama_mk VARCHAR(100) NOT NULL,
  sks TINYINT NOT NULL,
  semester_paket TINYINT NOT NULL DEFAULT 1,
  FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi),
  CHECK (sks BETWEEN 1 AND 6)
) ENGINE=InnoDB;

CREATE TABLE tahun_ajaran (
  id_ta INT AUTO_INCREMENT PRIMARY KEY,
  tahun VARCHAR(9) NOT NULL,
  semester ENUM('ganjil','genap') NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_ta (tahun, semester)
) ENGINE=InnoDB;

CREATE TABLE kelas (
  id_kelas INT AUTO_INCREMENT PRIMARY KEY,
  id_mk INT NOT NULL,
  id_dosen INT NOT NULL,
  id_ta INT NOT NULL,
  nama_kelas VARCHAR(5) NOT NULL,
  hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  ruang VARCHAR(20) NOT NULL,
  kuota INT NOT NULL DEFAULT 40,
  UNIQUE KEY uq_kelas (id_mk, id_ta, nama_kelas),
  FOREIGN KEY (id_mk) REFERENCES mata_kuliah(id_mk),
  FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen),
  FOREIGN KEY (id_ta) REFERENCES tahun_ajaran(id_ta)
) ENGINE=InnoDB;

CREATE TABLE krs (
  id_krs INT AUTO_INCREMENT PRIMARY KEY,
  id_mahasiswa INT NOT NULL,
  id_kelas INT NOT NULL,
  status ENUM('diajukan','disetujui','ditolak') NOT NULL DEFAULT 'diajukan',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_krs (id_mahasiswa, id_kelas),
  FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa(id_mahasiswa) ON DELETE CASCADE,
  FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE nilai (
  id_nilai INT AUTO_INCREMENT PRIMARY KEY,
  id_krs INT NOT NULL UNIQUE,
  tugas DECIMAL(5,2) NOT NULL DEFAULT 0,
  uts DECIMAL(5,2) NOT NULL DEFAULT 0,
  uas DECIMAL(5,2) NOT NULL DEFAULT 0,
  nilai_akhir DECIMAL(5,2) NOT NULL DEFAULT 0,
  huruf VARCHAR(2) NOT NULL DEFAULT 'E',
  bobot DECIMAL(3,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_krs) REFERENCES krs(id_krs) ON DELETE CASCADE,
  CHECK (tugas BETWEEN 0 AND 100 AND uts BETWEEN 0 AND 100 AND uas BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE INDEX idx_kelas_ta ON kelas(id_ta);
CREATE INDEX idx_kelas_dosen ON kelas(id_dosen);
CREATE INDEX idx_krs_mhs ON krs(id_mahasiswa);
