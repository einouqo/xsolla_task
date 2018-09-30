/*
 Navicat Premium Data Transfer

 Source Server         : GNU_Linux_MySQL
 Source Server Type    : MySQL
 Source Server Version : 50723
 Source Host           : 192.168.100.123:3306
 Source Schema         : PHP04

 Target Server Type    : MySQL
 Target Server Version : 50723
 File Encoding         : 65001

 Date: 30/09/2018 22:09:27
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for addresses
-- ----------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `id_company` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `address` (`address`),
  KEY `id_company` (`id_company`),
  CONSTRAINT `id_company` FOREIGN KEY (`id_company`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for company
-- ----------------------------
DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `access_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for delivery
-- ----------------------------
DROP TABLE IF EXISTS `delivery`;
CREATE TABLE `delivery` (
  `address` varchar(255) NOT NULL,
  `id_item` int(11) NOT NULL,
  `size` varchar(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `delivery_ibfk_1` (`address`),
  KEY `delivery_ibfk_2` (`id_item`),
  KEY `size` (`size`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`address`) REFERENCES `infoWarehouses` (`address`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for infoWarehouses
-- ----------------------------
DROP TABLE IF EXISTS `infoWarehouses`;
CREATE TABLE `infoWarehouses` (
  `address` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  PRIMARY KEY (`address`),
  UNIQUE KEY `address` (`address`),
  CONSTRAINT `infoWarehouses_ibfk_1` FOREIGN KEY (`address`) REFERENCES `addresses` (`address`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for items
-- ----------------------------
DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price` float NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for personalInfo
-- ----------------------------
DROP TABLE IF EXISTS `personalInfo`;
CREATE TABLE `personalInfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `phone` varchar(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for positions
-- ----------------------------
DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of positions
-- ----------------------------
BEGIN;
INSERT INTO `positions` VALUES (0, 'employee');
INSERT INTO `positions` VALUES (1, 'admin');
COMMIT;

-- ----------------------------
-- Table structure for quantity
-- ----------------------------
DROP TABLE IF EXISTS `quantity`;
CREATE TABLE `quantity` (
  `address` varchar(255) NOT NULL,
  `id_item` int(11) NOT NULL,
  `size` varchar(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`address`,`id_item`,`size`),
  KEY `quantity_ibfk_2` (`id_item`),
  CONSTRAINT `quantity_ibfk_1` FOREIGN KEY (`address`) REFERENCES `infoWarehouses` (`address`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `quantity_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for selling
-- ----------------------------
DROP TABLE IF EXISTS `selling`;
CREATE TABLE `selling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_address` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `size` varchar(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` float NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_item` (`id_item`),
  KEY `selling_ibfk_2` (`id_address`),
  CONSTRAINT `selling_ibfk_2` FOREIGN KEY (`id_address`) REFERENCES `addresses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `selling_ibfk_3` FOREIGN KEY (`id_item`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for transfer
-- ----------------------------
DROP TABLE IF EXISTS `transfer`;
CREATE TABLE `transfer` (
  `id_history` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `size` varchar(11) NOT NULL,
  PRIMARY KEY (`id_history`,`id_item`,`size`) USING BTREE,
  KEY `transfer_ibfk_1` (`id_item`),
  CONSTRAINT `transfer_ibfk_1` FOREIGN KEY (`id_item`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transfer_ibfk_2` FOREIGN KEY (`id_history`) REFERENCES `transferHistory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for transferHistory
-- ----------------------------
DROP TABLE IF EXISTS `transferHistory`;
CREATE TABLE `transferHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_departure` datetime NOT NULL,
  `date_receiving` datetime DEFAULT NULL,
  `id_from` int(11) NOT NULL,
  `id_to` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transferHistory_ibfk_1` (`id_from`),
  KEY `transferHistory_ibfk_2` (`id_to`),
  CONSTRAINT `transferHistory_ibfk_1` FOREIGN KEY (`id_from`) REFERENCES `addresses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transferHistory_ibfk_2` FOREIGN KEY (`id_to`) REFERENCES `addresses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for userAccessible
-- ----------------------------
DROP TABLE IF EXISTS `userAccessible`;
CREATE TABLE `userAccessible` (
  `id_company` int(11) NOT NULL,
  `id_address` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  PRIMARY KEY (`id_address`,`id_user`),
  KEY `userAccessible_ibfk_1` (`id_company`),
  KEY `userAccessible_ibfk_3` (`id_user`),
  CONSTRAINT `userAccessible_ibfk_1` FOREIGN KEY (`id_company`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `userAccessible_ibfk_2` FOREIGN KEY (`id_address`) REFERENCES `addresses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `userAccessible_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_company` int(11) NOT NULL,
  `id_personalData` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `salt` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `users_ibfk_1` (`id_company`),
  KEY `users_ibfk_2` (`id_personalData`),
  KEY `users_ibfk_3` (`position`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_company`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`id_personalData`) REFERENCES `personalInfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_3` FOREIGN KEY (`position`) REFERENCES `positions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Triggers structure for table selling
-- ----------------------------
DROP TRIGGER IF EXISTS `clean_items`;
delimiter ;;
CREATE TRIGGER `clean_items` AFTER DELETE ON `selling` FOR EACH ROW BEGIN
DECLARE helper INT(11);
   SET helper := (SELECT COUNT(*) 
	 FROM `selling` 
	 WHERE id_item = OLD.id_item);
	 IF `helper` = 0 THEN BEGIN
			DELETE FROM `items` WHERE id = OLD.id_item;
			END;
	 END IF;
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
