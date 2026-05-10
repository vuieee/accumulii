# Accumulii

In Partial Fulfillment of the Requirements for WEB DEVELOPMENT 1

---

## Project Structure

```plaintext
Accumulii/
├── css/
│   ├── style.css         # Core terminal layout & character-grid logic
│   └── theme.css         # Dracula / Tokyo Night / GitHub Light tokens
├── js/
│   ├── api.php           # Backend communication (Auth & Banners)
│   ├── commands.js       # Command logic & repository data
│   └── api.js            # Fetch wrapper for PHP endpoints
├── index.php             # Main terminal interface & input loop
├── login.php             # Secure auth gate with boot simulation
├── register.php          # System instance initialization
├── config.php            # Session management & DB connection
└── database.sql          # Legacy DB schema (optional)
```
## Command Reference

| Command        | Description |
|----------------|-------------|
| fetchme        | Displays system specs and developer information in a Fastfetch-style layout |
| repos          | Lists all hard-coded repositories in a structured table |
| repo <name>    | Shows detailed information about a specific repository |
| online         | Displays currently connected system instances |
| theme <name>   | Switches terminal theme (dark, ash, white) |
| whoami         | Displays the active session user |
| clear          | Clears the terminal buffer |
| logout         | Ends session and returns to authentication gate |
