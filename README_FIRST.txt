===========================================================
FUSIONVERSE — PORTABLE SETUP GUIDE
===========================================================

To run this project on another PC or Server without errors:

METHOD 1: ONE-CLICK AUTOMATED SETUP (Recommended)
-------------------------------------------------
1. Copy the entire project folder to the new PC.
2. Ensure XAMPP/WAMP is running (Apache & MySQL).
3. Open your browser and go to: http://localhost/it-fest/install.php
4. The system will automatically create the database, all tables, 
   and a master admin account.
5. Once done, delete 'install.php' for security.

METHOD 2: MANUAL SQL IMPORT
---------------------------
1. Open phpMyAdmin on the new PC.
2. Create a new database named: fusionverse_db
3. Select the database and click 'Import'.
4. Choose the 'fusionverse_database.sql' file from this folder.
5. Click 'Go'.

DEFAULT LOGIN CREDENTIALS:
-------------------------
Admin Email: admin@fusionverse.com
Admin Password: admin123

TROUBLESHOOTING:
----------------
If you see 'Database Connection Failed', check:
- Is MySQL running?
- Open 'config/db.php' and ensure $user and $pass match 
  your local XAMPP/WAMP settings (usually User: root, Pass: empty).

===========================================================
FUSIONVERSE 2026 | BUILT FOR INNOVATION
===========================================================
