CREATE TABLE DRECPTS (
  rcp_number INTEGER NOT NULL,
  rcp_line SMALLINT NOT NULL,
  po_line INTEGER UNSIGNED NULL,
  shadow INTEGER NULL,
  p_l CHAR NOT NULL,
  part_number CHAR NOT NULL,
  upc CHAR NOT NULL,
  pkg_qty INTEGER NOT NULL,
  pkg_uom CHAR(3)) NULL,
  qty_ship INTEGER NOT NULL,
  qty_ord INTEGER NOT NULL,
  qty_recvd INTEGER NOT NULL,
  qty_dropd INTEGER NOT NULL, -- ???
  qty_stock INTEGER NOT NULL,
  uom CHAR(3) NOT NULL,
  man_qty_ovrd CHAR(1) NOT NULL,
  serial_number VARCHAR(30) NOT NULL,
  recv_to CHAR(1) NULL,
  bin_tote CHAR(18) NOT NULL,
  rcp_status TINYINT NOT NULL,
  mdse_price DECIMAL NOT NULL,
  core_price DECIMAL NOT NULL,
  PRIMARY KEY(rcp_number, rcp_line),
  INDEX DRECPTS_idx1(rcp_number, rcp_line)
);
