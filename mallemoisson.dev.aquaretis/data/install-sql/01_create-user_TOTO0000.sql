-- Utilisateur 'TOTO0000'
SET AUTOCOMMIT=0;
INSERT INTO users (`cuid`, `password`, `salt`, `active`)  VALUES ('TOTO0000', 'f9a4057d02006bb7de62c6873c139d15', '2e0853dc', '1');
INSERT INTO acl_role_user (`id_acl_role`, `cuid`) VALUES ('1', 'TOTO0000');
COMMIT;
