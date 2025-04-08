# Virtual Art Gallery

A web-based *Virtual Art Gallery* platform designed to showcase, explore, and buy/sell digital artworks. Users can browse different categories, mark favourites, leave reviews, and artists can upload their masterpieces. The system supports transaction handling and category-wise artwork management.

---
---

## 📖 About

This project was developed as part of a college course submission with a focus on frontend, backend, and DBMS integration. It offers a real-time art exhibition experience where users and artists can engage through a visually appealing and responsive platform.

---

## 🚀 Features

- 👤 *User Authentication*
-🖼 *Artwork Display with Filters & Categories*
- 💬 *Reviews & Ratings*
-❤ *Add to Favourites*
- 🛒 *Transaction System*
- 🔔 *User Notifications*
- 🎨 *Artwork Upload for Artists*
- 📁 *Admin Control Panel*

---

## 🛠 Tech Stack

- *Frontend*: HTML, CSS, JavaScript
- *Backend*: PHP
- *Database*: MySQL
- *Server*: Apache (XAMPP)

---

## 🗃 Database Schema

Here are the main tables involved in the system:

1. *Users* – (user_id, name, email, password, role)
2. *Artworks* – (artwork_id, user_id, title, description, image, price, date_posted)
3. *Categories* – (category_id, category_name)
4. *Artworks_Categories* – (artwork_id, category_id)
5. *Transactions* – (transaction_id, buyer_id, artwork_id, amount, date)
6. *Reviews* – (review_id, user_id, artwork_id, rating, comment)
7. *Favourites* – (fav_id, user_id, artwork_id)
8. *Notifications* – (notification_id, user_id, message, status, timestamp)

---

## 🖼 Screenshots

---

## ⚙ Setup Instructions

1. *Clone the Repository*
   ```bash
   git clone https://github.com/yourusername/virtual-art-gallery.git
   cd virtual-art-gallery
```

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="animations.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</rewritten_file>