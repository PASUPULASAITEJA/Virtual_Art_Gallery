-- Create database
CREATE DATABASE IF NOT EXISTS virtual_art_gallery;
USE virtual_art_gallery;

-- Users table
CREATE TABLE if not exists Users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'user',
    profile_picture VARCHAR(255),
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_username (username)
);

-- Categories table
CREATE TABLE if not exists Categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    UNIQUE KEY unique_category_name (category_name)
);

-- Artworks table
CREATE TABLE if not exists Artworks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    artist_id INT NOT NULL,
    owner_id INT NOT NULL,
    status ENUM('available', 'sold', 'auction') DEFAULT 'available',
    auction_end_time DATETIME NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES Users(id),
    FOREIGN KEY (owner_id) REFERENCES Users(id)
);

-- Artwork_Categories table
CREATE TABLE if not exists Artwork_Categories (
    artwork_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (artwork_id, category_id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(id),
    FOREIGN KEY (category_id) REFERENCES Categories(id)
);

-- Reviews table
CREATE TABLE if not exists Reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artwork_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artwork_id) REFERENCES Artworks(id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    UNIQUE KEY unique_user_artwork_review (user_id, artwork_id)
);

-- Transactions table
CREATE TABLE if not exists Transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artwork_id INT NOT NULL,
    buyer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artwork_id) REFERENCES Artworks(id),
    FOREIGN KEY (buyer_id) REFERENCES Users(id)
);

-- Favorites table
CREATE TABLE if not exists Favorites (
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    PRIMARY KEY (user_id, artwork_id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(id)
);

-- Notifications table
CREATE TABLE if not exists Notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);


-- Insert some default categories
INSERT INTO Categories (category_name) VALUES
    ('Painting'),
    ('Photography'),
    ('Digital Art'),
    ('Sculpture'),
    ('Drawing'),
    ('Mixed Media'),
    ('Illustration'),
    ('Print Making'); 

INSERT INTO Reviews (artwork_id, user_id, rating, review_text, created_at) VALUES (?, ?, ?, '', NOW())

USE virtual_art_gallery;

-- Add auction_end_time column to Artworks table
ALTER TABLE Artworks 
ADD COLUMN auction_end_time DATETIME NULL AFTER status;

-- Update status enum to include 'auction'
ALTER TABLE Artworks 
MODIFY COLUMN status ENUM('available', 'sold', 'auction', 'reserved') DEFAULT 'available';