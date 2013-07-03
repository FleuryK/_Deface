CREATE TABLE IF NOT EXISTS `_deface` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `hash` varchar(32) NOT NULL,
  UNIQUE KEY `file_id` (`file_id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
