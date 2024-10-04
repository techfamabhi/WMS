
ALTER TABLE ORDTOTE add tote_status smallint default 0;


update ORDTOTE set tote_status = 0;
