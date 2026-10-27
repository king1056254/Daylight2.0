-- Optional chat log for the website assistant (only written when ASSISTANT_LOG_CHATS=true).
CREATE TABLE IF NOT EXISTS assistant_messages (
  id CHAR(36) NOT NULL PRIMARY KEY,
  session_hash CHAR(64) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content TEXT NOT NULL,
  mode VARCHAR(10) NOT NULL DEFAULT 'faq',
  matched TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_assistant_created (created_at),
  KEY idx_assistant_session (session_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
