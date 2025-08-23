# 📝 Collab Notes

A simple **collaborative note-taking web app** built with **PHP, MySQL, and plain CSS**.  
Users can create notes, share them with others, and edit them in real time with basic conflict handling.

---

##  Features
-  Create, edit, and delete personal notes  
-  Share notes with other registered users  
-  Search and filter notes by title or date range  
-  Conflict detection when two users edit the same note  
-  User authentication (signup, login, logout)

---

## ⚙️ Setup Instructions
1. Clone this repo
  
2. Import database
  -Open phpMyAdmin
  -Create a DB named notes_app
  -Import the file notes_app.sql

3. Run
  -Put the project folder inside htdocs (if using XAMPP)
  -Start Apache & MySQL
  -Visit http://localhost/collab-notes/signup.php
