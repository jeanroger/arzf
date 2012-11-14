SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `acl_resources`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_resources` ;

CREATE  TABLE IF NOT EXISTS `acl_resources` (
  `id_acl_resource` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(150) NOT NULL ,
  PRIMARY KEY (`id_acl_resource`) ,
  UNIQUE INDEX `uk_acl_resource_name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `acl_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_roles` ;

CREATE  TABLE IF NOT EXISTS `acl_roles` (
  `id_acl_role` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(25) NOT NULL ,
  `fullname` VARCHAR(150) NOT NULL ,
  PRIMARY KEY (`id_acl_role`) ,
  UNIQUE INDEX `uk_acl_role_name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `acl_asserts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_asserts` ;

CREATE  TABLE IF NOT EXISTS `acl_asserts` (
  `id_acl_assert` INT UNSIGNED NOT NULL ,
  `name` VARCHAR(45) NULL ,
  `class` VARCHAR(150) NULL ,
  PRIMARY KEY (`id_acl_assert`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `acl_role_resource`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_role_resource` ;

CREATE  TABLE IF NOT EXISTS `acl_role_resource` (
  `id_acl_role` INT UNSIGNED NOT NULL ,
  `id_acl_resource` INT UNSIGNED NOT NULL COMMENT 'NULL for all resource' ,
  `allow` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' ,
  `id_acl_assert` INT UNSIGNED NULL ,
  UNIQUE INDEX `uk_acl_role_resource_privilege1` (`id_acl_role` ASC, `id_acl_resource` ASC) ,
  INDEX `fk_acl_role_resource_2` (`id_acl_resource` ASC) ,
  INDEX `fk_acl_role_resource_3` (`id_acl_assert` ASC) ,
  PRIMARY KEY (`id_acl_role`, `id_acl_resource`) ,
  CONSTRAINT `fk_acl_role_resource_1`
    FOREIGN KEY (`id_acl_role` )
    REFERENCES `acl_roles` (`id_acl_role` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_acl_role_resource_2`
    FOREIGN KEY (`id_acl_resource` )
    REFERENCES `acl_resources` (`id_acl_resource` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_acl_role_resource_3`
    FOREIGN KEY (`id_acl_assert` )
    REFERENCES `acl_asserts` (`id_acl_assert` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `acl_role_role`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_role_role` ;

CREATE  TABLE IF NOT EXISTS `acl_role_role` (
  `id_acl_role` INT UNSIGNED NOT NULL ,
  `id_acl_role_parent` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id_acl_role`, `id_acl_role_parent`) ,
  INDEX `fk_acl_role_parent` (`id_acl_role_parent` ASC) ,
  CONSTRAINT `fk_acl_role_role`
    FOREIGN KEY (`id_acl_role` )
    REFERENCES `acl_roles` (`id_acl_role` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_acl_role_parent`
    FOREIGN KEY (`id_acl_role_parent` )
    REFERENCES `acl_roles` (`id_acl_role` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE  TABLE IF NOT EXISTS `users` (
  `id_user` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `cuid` CHAR(8) NOT NULL ,
  `password` CHAR(32) NOT NULL ,
  `salt` CHAR(8) NOT NULL ,
  `active` TINYINT(1) NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`id_user`) ,
  UNIQUE INDEX `cuid_UNIQUE` (`cuid` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `acl_role_user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_role_user` ;

CREATE  TABLE IF NOT EXISTS `acl_role_user` (
  `id_acl_role_user` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `id_acl_role` INT UNSIGNED NOT NULL ,
  `cuid` CHAR(8) NOT NULL ,
  PRIMARY KEY (`id_acl_role_user`) ,
  INDEX `fk_acl_roles1` (`id_acl_role` ASC) ,
  UNIQUE INDEX `uk_acl_role_user` (`id_acl_role` ASC, `cuid` ASC) ,
  CONSTRAINT `fk_acl_roles1`
    FOREIGN KEY (`id_acl_role` )
    REFERENCES `acl_roles` (`id_acl_role` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `acl_resources`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO acl_resources (`id_acl_resource`, `name`) VALUES ('1', 'mvc.default');

COMMIT;

-- -----------------------------------------------------
-- Data for table `acl_roles`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO acl_roles (`id_acl_role`, `name`, `fullname`) VALUES ('1', 'administrators', 'Administrateurs');
INSERT INTO acl_roles (`id_acl_role`, `name`, `fullname`) VALUES ('2', 'guests', 'Invités');

COMMIT;

-- -----------------------------------------------------
-- Data for table `acl_role_resource`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO acl_role_resource (`id_acl_role`, `id_acl_resource`, `allow`, `id_acl_assert`) VALUES ('2', '1', '1', NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `acl_role_user`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO acl_role_user (`id_acl_role_user`, `id_acl_role`, `cuid`) VALUES ('1', '1', 'admin');
INSERT INTO acl_role_user (`id_acl_role_user`, `id_acl_role`, `cuid`) VALUES ('2', '2', 'guest');

COMMIT;
