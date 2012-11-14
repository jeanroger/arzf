-- Utilisateur 'RDEFLESSELLE@CREDEAU.COM'
SET AUTOCOMMIT=0;
INSERT INTO users (`cuid`, `password`, `salt`, `active`)  VALUES ('RDEFLESSELLE@CREDEAU.COM', 'ec7c5597ae71c2531bafef20a9e0cbef', '6389f43b', '1');
INSERT INTO acl_role_user (`id_acl_role`, `cuid`) VALUES ('1', 'RDEFLESSELLE@CREDEAU.COM');
COMMIT;
