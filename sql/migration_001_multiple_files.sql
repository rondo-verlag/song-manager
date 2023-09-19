INSERT INTO files (songId, name, mime, type, filesize, rawData)
SELECT id AS songId, 'CopyrightVertrag.pdf', 'application/pdf', 'copyright', OCTET_LENGTH(rawCopyrightPDF) AS filesize, rawCopyrightPDF AS rawData
FROM songs
WHERE rawCopyrightPDF != '';

# ALTER TABLE `songs` DROP `rawCopyrightPDF`;
