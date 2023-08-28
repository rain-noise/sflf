use sflf;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INTEGER PRIMARY KEY,
    `name` TEXT NOT NULL,
    `gender` INTEGER NOT NULL,
    `birthday` DATE NOT NULL,
    `email` TEXT NOT NULL,
    `role` VARCHAR(6) NOT NULL DEFAULT 'user',
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `article_id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `user_id` INTEGER NOT NULL,
    `subject` VARCHAR(30) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;
