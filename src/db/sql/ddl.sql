CREATE TABLE `user` (
    id bigint NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    email varchar(256) NOT NULL, 
	password varchar(256) NOT NULL,
	gender int NULL,
	phone varchar(30) NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	token varchar(256) NULL,
	PRIMARY KEY(id),
	UNIQUE (email)

)ENGINE=Innodb;
CREATE TABLE recipe (
    id bigint NOT NULL AUTO_INCREMENT,
	user_id bigint NOT NULL,
	title varchar(50) NOT NULL,
	description varchar(256) NOT NULL,
	cover_image varchar(256) NOT NULL,
	tips varchar(256) NULL,
	category int NOT NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
	CONSTRAINT FK_user_recipe FOREIGN KEY (user_id)
    REFERENCES `user`(id)
   
)ENGINE=Innodb;
CREATE TABLE category (
    id bigint NOT NULL AUTO_INCREMENT,
	name varchar(50) NOT NULL,
    PRIMARY KEY(id),
    creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
)ENGINE=Innodb;

CREATE TABLE ingredient (
    id bigint NOT NULL AUTO_INCREMENT,
	name  varchar(50) NOT NULL,
	recipe_id  bigint NOT NULL,
	amount int NOT NULL,
	unit varchar(50) NOT NULL,
	PRIMARY KEY(id),
    CONSTRAINT FK_recipe_ingredient FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE instruction (
    id bigint NOT NULL AUTO_INCREMENT,
	recipe_id  bigint NOT NULL,
	media varchar(256) NULL,
	description varchar(50) NOT NULL,
	step int NOT NULL,
    PRIMARY KEY(id),
	CONSTRAINT FK_recipe_instruction FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE album(
    id bigint NOT NULL AUTO_INCREMENT,
	user_id bigint NOT NULL,
	name varchar(50) NOT NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
	CONSTRAINT FK_user_album FOREIGN KEY (user_id)
    REFERENCES `user`(id)
)ENGINE=Innodb;

CREATE TABLE album_recipe(
	album_id  bigint NOT NULL,
	recipe_id bigint NOT NULL,
	CONSTRAINT FK_album_ar FOREIGN KEY (album_id)
    REFERENCES album(id),	
	CONSTRAINT FK_recipe_ar FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE user_follower(
    id bigint NOT NULL AUTO_INCREMENT,
	user_id bigint NOT NULL,
	following_id bigint NOT NULL,
    following_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
	CONSTRAINT FK_user_ar FOREIGN KEY (user_id)
    REFERENCES `user`(id)
)ENGINE=Innodb;

CREATE TABLE `comment`(
    id bigint NOT NULL AUTO_INCREMENT,
	user_id bigint NOT NULL,
	recipe_id bigint NOT NULL,
    content varchar(500) NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT FK_user_comment FOREIGN KEY (user_id)
    REFERENCES `user`(id),
    PRIMARY KEY(id),
	CONSTRAINT FK_recipe_comment FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE `like`(
    id bigint NOT NULL AUTO_INCREMENT,
	user_id bigint NOT NULL ,
	recipe_id bigint NOT NULL,
    islike int NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
	CONSTRAINT FK_user_like FOREIGN KEY (user_id)
    REFERENCES `user`(id),
	CONSTRAINT FK_recipe_like FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE ratings(
    id bigint NOT NULL AUTO_INCREMENT,
	recipe_id bigint NOT NULL,
	user_id bigint NOT NULL ,
	ratings decimal(1,1) NOT NULL,
	creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
	CONSTRAINT FK_user_ratings FOREIGN KEY (user_id)
    REFERENCES `user`(id),
	CONSTRAINT FK_recipe_ratings FOREIGN KEY (recipe_id)
    REFERENCES recipe(id)
)ENGINE=Innodb;

CREATE TABLE allergies_categories(
    id bigint NOT NULL AUTO_INCREMENT,
    allergy_name varchar(64) NOT NULL,
    creation_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)ENGINE=Innodb;

CREATE TABLE user_allergy(
    id bigint NOT NULL AUTO_INCREMENT,
    user_id bigint NOT NULL ,
    allergies_categories_id bigint NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT FK_user_user_allgergy FOREIGN KEY (user_id)
    REFERENCES `user`(id),
    CONSTRAINT FK_UA_ACI FOREIGN KEY (allergies_categories_id)
    REFERENCES `allergies_categories`(id)
)ENGINE=Innodb;
CREATE TABLE search_history(
     id bigint NOT NULL AUTO_INCREMENT,
     user_id bigint NOT NULL ,
     keyword bigint NOT NULL,
     PRIMARY KEY(id),
     CONSTRAINT FK_user_search_history FOREIGN KEY (user_id)
     REFERENCES `user`(id)
)ENGINE=Innodb;


