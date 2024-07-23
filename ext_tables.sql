#
# Table structure for table 'tx_searchable_domain_model_update'
#
CREATE TABLE tx_searchable_domain_model_update (
	type varchar(255) NOT NULL,
	property varchar(255) NOT NULL,
	property_uid int(11) NOT NULL,

	UNIQUE KEY element (type, property, property_uid)
);
