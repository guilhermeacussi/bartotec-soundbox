CREATE DATABASE IF NOT EXISTS soundbox
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_unicode_ci;

USE soundbox;

-- =========================
-- USERS
-- =========================
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE=InnoDB;

-- =========================
-- ARTISTS
-- =========================
CREATE TABLE artists (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    biography TEXT NULL,
    photo VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    birth_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- =========================
-- ALBUMS
-- =========================
CREATE TABLE albums (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    artist_id INT NOT NULL,
    release_year YEAR NULL,
    cover VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY artist_id (artist_id),
    CONSTRAINT fk_album_artist
        FOREIGN KEY (artist_id) REFERENCES artists(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- SONGS
-- =========================
CREATE TABLE songs (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    artist_id INT NOT NULL,
    album_id INT NULL,
    duration INT NULL,
    release_year YEAR NULL,
    lyrics LONGTEXT NULL,
    spotify_url VARCHAR(255) NULL,
    youtube_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY artist_id (artist_id),
    KEY album_id (album_id),
    CONSTRAINT fk_song_artist
        FOREIGN KEY (artist_id) REFERENCES artists(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_song_album
        FOREIGN KEY (album_id) REFERENCES albums(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- GENRES
-- =========================
CREATE TABLE genres (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB;

-- =========================
-- SONG_GENRES (N:N)
-- =========================
CREATE TABLE song_genres (
    song_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (song_id, genre_id),
    KEY genre_id (genre_id),
    CONSTRAINT fk_sg_song
        FOREIGN KEY (song_id) REFERENCES songs(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sg_genre
        FOREIGN KEY (genre_id) REFERENCES genres(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- AWARDS
-- =========================
CREATE TABLE awards (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    year YEAR NOT NULL,
    category VARCHAR(200) NOT NULL,
    winner_type ENUM('artist', 'song', 'album') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- =========================
-- AWARD_WINNERS
-- =========================
CREATE TABLE award_winners (
    award_id INT NOT NULL,
    winner_id INT NOT NULL,
    PRIMARY KEY (award_id, winner_id),
    CONSTRAINT fk_award
        FOREIGN KEY (award_id) REFERENCES awards(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- REVIEWS
-- =========================
CREATE TABLE reviews (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY song_id (song_id),
    CONSTRAINT fk_review_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_review_song
        FOREIGN KEY (song_id) REFERENCES songs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- FAVORITES
-- =========================
CREATE TABLE favorites (
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, song_id),
    KEY song_id (song_id),
    CONSTRAINT fk_fav_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_fav_song
        FOREIGN KEY (song_id) REFERENCES songs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- FOLLOWERS
-- =========================
CREATE TABLE followers (
    id INT NOT NULL AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_follow (follower_id, following_id),
    KEY following_id (following_id),
    CONSTRAINT fk_follower_user
        FOREIGN KEY (follower_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_following_user
        FOREIGN KEY (following_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
