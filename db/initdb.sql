CREATE TABLE api_key (
  id INTEGER PRIMARY KEY,
  api_key TEXT NOT NULL
);

CREATE TABLE link (
  api_key_id INTEGER NOT NULL,
  code TEXT NOT NULL,
  destination TEXT NOT NULL,
  FOREIGN KEY(api_key_id) REFERENCES api_key(id)
);
