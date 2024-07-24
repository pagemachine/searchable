CREATE TABLE tx_searchable_domain_model_update (
	uid int(11) NOT NULL auto_increment,

	type varchar(255) NOT NULL,
	property varchar(255) NOT NULL,
	property_uid int(11) NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY element (type, property, property_uid)
);
