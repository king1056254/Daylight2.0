-- Client portal tables (schema existed in the original; UI is new).
CREATE TABLE IF NOT EXISTS clients (
  id CHAR(36) NOT NULL PRIMARY KEY,
  user_id CHAR(36) NULL, company_name VARCHAR(200) NOT NULL, contact_name VARCHAR(200) NULL,
  email VARCHAR(254) NULL, phone VARCHAR(40) NULL, industry VARCHAR(200) NULL, notes TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_clients_user (user_id),
  CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_projects (
  id CHAR(36) NOT NULL PRIMARY KEY,
  client_id CHAR(36) NOT NULL, name VARCHAR(200) NOT NULL, description TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'In Progress', progress INT NOT NULL DEFAULT 0,
  start_date DATE NULL, expected_completion DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_client_projects_client (client_id),
  CONSTRAINT fk_client_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_milestones (
  id CHAR(36) NOT NULL PRIMARY KEY,
  project_id CHAR(36) NOT NULL, title VARCHAR(200) NOT NULL, description TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Pending', due_date DATE NULL, sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_milestones_project (project_id, sort_order),
  CONSTRAINT fk_milestones_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_documents (
  id CHAR(36) NOT NULL PRIMARY KEY,
  project_id CHAR(36) NOT NULL, title VARCHAR(200) NOT NULL, file_path VARCHAR(500) NULL, file_type VARCHAR(60) NULL,
  visible_to_client TINYINT(1) NOT NULL DEFAULT 1, uploaded_by CHAR(36) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_documents_project (project_id),
  CONSTRAINT fk_documents_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_messages (
  id CHAR(36) NOT NULL PRIMARY KEY,
  project_id CHAR(36) NOT NULL, sender_id CHAR(36) NULL, sender_name VARCHAR(200) NULL, body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_project (project_id, created_at),
  CONSTRAINT fk_messages_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_user FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
  id CHAR(36) NOT NULL PRIMARY KEY,
  client_id CHAR(36) NOT NULL, project_id CHAR(36) NULL, subject VARCHAR(250) NOT NULL, description TEXT NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'Normal', status VARCHAR(40) NOT NULL DEFAULT 'Open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_tickets_client (client_id, status),
  CONSTRAINT fk_tickets_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_tickets_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id CHAR(36) NOT NULL PRIMARY KEY,
  invoice_number VARCHAR(40) NOT NULL, client_id CHAR(36) NOT NULL, project_id CHAR(36) NULL,
  currency CHAR(3) NOT NULL DEFAULT 'KES', subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, tax DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0, status VARCHAR(40) NOT NULL DEFAULT 'Draft',
  issue_date DATE NULL, due_date DATE NULL, notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_number (invoice_number), KEY idx_invoices_client (client_id, status),
  CONSTRAINT fk_invoices_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_invoices_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
  id CHAR(36) NOT NULL PRIMARY KEY,
  invoice_id CHAR(36) NOT NULL, description VARCHAR(400) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1, unit_price DECIMAL(12,2) NOT NULL DEFAULT 0, amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  KEY idx_items_invoice (invoice_id),
  CONSTRAINT fk_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id CHAR(36) NOT NULL PRIMARY KEY,
  invoice_id CHAR(36) NOT NULL, amount DECIMAL(12,2) NOT NULL, method VARCHAR(60) NULL DEFAULT 'M-Pesa',
  reference VARCHAR(120) NULL, paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_payments_invoice (invoice_id),
  CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
