ALTER TABLE `songs` ADD `copyrightPublisher` VARCHAR(1000) NOT NULL AFTER `copyrightStatusBook2024`;
ALTER TABLE `songs` ADD `copyrightCommentApp` LONGTEXT NOT NULL AFTER `copyrightPublisher`;
ALTER TABLE `songs` ADD `license_type_app` ENUM('UNKNOWN','ONE_TIME','PRO_RATA_50','FREE','OTHER') NOT NULL DEFAULT 'UNKNOWN' AFTER `license_type`;
ALTER TABLE `songs` ADD `licenseAppUntil` DATE NULL AFTER `license_type_app`;

# populate license_type_app
UPDATE songs SET license_type_app = 'FREE' WHERE license_type = 'FREE';
