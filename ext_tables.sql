
CREATE TABLE tx_sudhaus7gpgadmin_domain_model_gpgkey (
    email varchar(255) NOT NULL DEFAULT '',
    pgp_public_key text,
    key email_idx (email)
);
