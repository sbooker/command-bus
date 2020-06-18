CREATE TABLE command (
    id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
    `name` VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
    result LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)',
    status VARCHAR(63) NOT NULL COMMENT '(DC2Type:command_status)',
    changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetimetz_immutable)',
    count INT NOT NULL,
    next_attempt_at DATETIME NOT NULL COMMENT '(DC2Type:datetimetz_immutable)',
    PRIMARY KEY(id),
    INDEX ix_command_name_next_attempt_at (`name`, next_attempt_at)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
