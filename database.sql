-- ============================================================
-- Reality Social · base de datos PROPIA (independiente)
-- Base sugerida en Hostinger: u712716917_social
-- ------------------------------------------------------------
-- La IDENTIDAD (login, correo, nombre, usuario y medio de conexión
-- Google/X/correo) vive en la base COMPARTIDA u712716917_market, tabla
-- `users`. Aquí NO se copian los usuarios: `user_id` se refiere a
-- users.id de esa base compartida. Así todos tus proyectos comparten
-- el mismo inicio de sesión, pero cada uno guarda sus datos aparte.
-- Importar en phpMyAdmin DENTRO de la base u712716917_social.
-- ============================================================
SET NAMES utf8mb4;

-- Perfil social (uno por usuario): lo propio de esta red.
CREATE TABLE IF NOT EXISTS profiles (
  user_id    INT UNSIGNED NOT NULL,
  bio        VARCHAR(160) NULL,
  location   VARCHAR(60)  NULL,
  link       VARCHAR(120) NULL,
  avatar     VARCHAR(80)  NULL,            -- archivo en uploads/avatars
  banner     VARCHAR(80)  NULL,            -- archivo en uploads/banners
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publicaciones: posts, respuestas (reply_to) y reposts citados (repost_of).
CREATE TABLE IF NOT EXISTS posts (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(280) NOT NULL DEFAULT '',
  image      VARCHAR(80)  NULL,            -- archivo en uploads/posts
  reply_to   BIGINT UNSIGNED NULL,
  repost_of  BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,                -- borrado suave
  PRIMARY KEY (id),
  KEY idx_posts_user (user_id, id),
  KEY idx_posts_reply (reply_to, id),
  KEY idx_posts_repost (repost_of)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Me gusta
CREATE TABLE IF NOT EXISTS likes (
  user_id    INT UNSIGNED NOT NULL,
  post_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id),
  KEY idx_likes_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reposts (para contar y para el timeline)
CREATE TABLE IF NOT EXISTS reposts (
  user_id    INT UNSIGNED NOT NULL,
  post_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id),
  KEY idx_reposts_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seguir / seguidores (user_id de la base compartida)
CREATE TABLE IF NOT EXISTS follows (
  follower_id INT UNSIGNED NOT NULL,
  followee_id INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, followee_id),
  KEY idx_follows_followee (followee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hashtags
CREATE TABLE IF NOT EXISTS hashtags (
  id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tag VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_hashtags (
  post_id    BIGINT UNSIGNED NOT NULL,
  hashtag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, hashtag_id),
  KEY idx_ph_tag (hashtag_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menciones (@usuario)
CREATE TABLE IF NOT EXISTS mentions (
  post_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,          -- usuario mencionado
  PRIMARY KEY (post_id, user_id),
  KEY idx_mentions_user (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones (nuevos seguidores, likes, respuestas, menciones, reposts)
CREATE TABLE IF NOT EXISTS notifications (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,       -- destinatario
  actor_id   INT UNSIGNED NOT NULL,       -- quien la provoca
  type       ENUM('follow','like','reply','mention','repost') NOT NULL,
  post_id    BIGINT UNSIGNED NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
