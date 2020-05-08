SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `wcf1_pinehearst_registry_location` (
  `locationID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locationName` varchar(32) NOT NULL,
  `boardID` int(11) DEFAULT NULL,
	`groupID` int(11) DEFAULT NULL,
  PRIMARY KEY (`locationID`),
  KEY `wcf1_pinehearst_registry_location_boardID_idx` (`boardID`),
  KEY `wcf1_pinehearst_registry_location_groupID_idx` (`groupID`),
  CONSTRAINT `wcf1_pinehearst_registry_location_ibfk_1` FOREIGN KEY (`boardID`) REFERENCES `wbb1_board` (`boardID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wcf1_pinehearst_registry_location_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `wcf1_user_group` (`groupID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wcf1_pinehearst_registry_location` (`locationName`, `boardID`, `groupID`)
VALUES
	('Assentia', null, null),
	('Astoria State', null, null),
	('Freeland', null, null),
	('Laurentiana', null, null),
	('New Alcantara', null, null),
	('Serena', null, null);

CREATE TABLE `wcf1_pinehearst_registry_entry` (
  `entryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `parentID` int(10) unsigned DEFAULT NULL,
  `locationID` int(10) unsigned NOT NULL,
  `registeredOn` int unsigned NOT NULL,
  `postID` int(11) DEFAULT NULL,
  PRIMARY KEY (`entryID`),
  KEY `wcf1_pinehearst_registry_entry_userID_idx` (`userID`),
  KEY `wcf1_pinehearst_registry_entry_parentID_idx` (`parentID`),
  KEY `wcf1_pinehearst_registry_entry_locationID_idx` (`locationID`),
  KEY `wcf1_pinehearst_registry_entry_registeredOn_idx` (`registeredOn`),
  KEY `wcf1_pinehearst_registry_entry_postID_idx` (`postID`),
  CONSTRAINT `wcf1_pinehearst_registry_entry_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `wcf1_user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wcf1_pinehearst_registry_entry_ibfk_2` FOREIGN KEY (`parentID`) REFERENCES `wcf1_pinehearst_registry_entry` (`entryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wcf1_pinehearst_registry_entry_ibfk_3` FOREIGN KEY (`locationID`) REFERENCES `wcf1_pinehearst_registry_location` (`locationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wcf1_pinehearst_registry_entry_ibfk_4` FOREIGN KEY (`postID`) REFERENCES `wbb1_post` (`postID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
