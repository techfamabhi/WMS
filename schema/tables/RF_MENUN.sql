USE wms;
CREATE TABLE RF_MENU (
        menu_num smallint NOT NULL,
        menu_line smallint NOT NULL,
        menu_desc varchar(40) NULL,
        menu_image varchar(64) NULL,
        menu_url varchar(128) NULL,
        menu_priv smallint NULL,
        menu_target varchar(10) NULL,
        PRIMARY KEY (menu_line,menu_num)
);
~

