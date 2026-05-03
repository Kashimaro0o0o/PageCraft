USE pagecraft;

INSERT INTO users (username, password)
VALUES ('admin', '1234');

INSERT INTO sites (user_id, site_name)
VALUES (1, 'My First Site');

INSERT INTO pages (site_id, title, slug)
VALUES (1, 'Home', 'home');

INSERT INTO sections (page_id, type, content, position)
VALUES (1, 'text', 'Welcome to PageCraft!', 1);