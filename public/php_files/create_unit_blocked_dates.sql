-- Table to store blocked dates for maintenance or unavailability without creating reservation records
CREATE TABLE IF NOT EXISTS `unit_blocked_dates` (
  `block_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `block_type` enum('Not Available','Maintenance') NOT NULL DEFAULT 'Not Available',
  `remarks` text DEFAULT NULL,
  `created_by_user_id` int(11) NOT NULL,
  `created_by_role` enum('admin','unit owner') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`block_id`),
  KEY `idx_unit_dates` (`unit_id`, `start_date`, `end_date`),
  CONSTRAINT `fk_blocked_unit` FOREIGN KEY (`unit_id`) REFERENCES `units_table` (`unit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
