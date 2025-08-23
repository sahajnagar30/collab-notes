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

---

## ⚠️ Conflict Handling (Editing Notes)

- In the `notes` table, each row has an `updated_at` timestamp column.  
- When a user opens a note for editing, the current `updated_at` value is sent along with the form.  
- When saving:
  1. The app fetches the latest `updated_at` from the database.  
  2. If it matches the one the user had when they started editing → save is allowed.  
  3. If it does not match → conflict is detected.  

In case of conflict, the user sees:
- Their own unsaved changes (so they don’t lose what they typed).  
- The latest version of the note from the database.  

This allows the user to manually merge or reapply their changes instead of silently overwriting someone else’s edits.
