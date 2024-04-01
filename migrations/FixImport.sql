/* FIX data not imported correctly */
UPDATE `book` SET is_manga = 1
WHERE external_id IN(3366802,3366801,1271684,1271674,1271664,2110793,3408841);

UPDATE `reading` as r
    LEFT JOIN `book` as b on b.id = r.book_id
    SET is_owned = 1, is_borrowed = 0
WHERE b.external_id IN (100637,83190,2110165,58767,58766,1185215,3523304);

UPDATE `reading` as r
    LEFT JOIN `book` as b on b.id = r.book_id
    SET is_ebook = 1, is_borrowed = 0
WHERE b.external_id IN (2109734,2048805,2048775,2095063,3460933);
