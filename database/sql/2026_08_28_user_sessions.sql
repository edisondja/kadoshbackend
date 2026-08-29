-- Sesiones activas de usuarios (por tenant)
CREATE TABLE IF NOT EXISTS user_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  jti VARCHAR(64) NOT NULL,
  ip_address VARCHAR(45) NULL,
  ubicacion VARCHAR(255) NULL,
  user_agent TEXT NULL,
  dispositivo VARCHAR(120) NULL,
  navegador VARCHAR(120) NULL,
  last_active_at TIMESTAMP NULL,
  expires_at TIMESTAMP NOT NULL,
  revoked_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY user_sessions_jti_unique (jti),
  INDEX user_sessions_usuario_idx (usuario_id),
  INDEX user_sessions_activas_idx (revoked_at, expires_at),
  CONSTRAINT user_sessions_usuario_fk FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
