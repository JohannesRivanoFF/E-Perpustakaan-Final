TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER after_insert_peminjaman_kurangi_stok\nAFTER INSERT ON peminjaman\nFOR EACH ROW\nBEGIN\n    IF NEW.status = \'dipinjam\' THEN\n        UPDATE buku SET stok = stok - 1 WHERE id_buku = NEW.id_buku;\n    END IF;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER after_update_peminjaman_sync_stok\nAFTER UPDATE ON peminjaman\nFOR EACH ROW\nBEGIN\n    IF OLD.status = \'dipinjam\' AND NEW.status = \'dikembalikan\' THEN\n        UPDATE buku SET stok = stok + 1 WHERE id_buku = NEW.id_buku;\n    END IF;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER before_delete_peminjaman_restore_stok\nBEFORE DELETE ON peminjaman\nFOR EACH ROW\nBEGIN\n    IF OLD.status = \'dipinjam\' THEN\n        UPDATE buku SET stok = stok + 1 WHERE id_buku = OLD.id_buku;\n    END IF;\nEND'
sql_modes=1098907648 1098907648 1098907648
definers='root@localhost' 'root@localhost' 'root@localhost'
client_cs_names='utf8mb4' 'utf8mb4' 'utf8mb4'
connection_cl_names='utf8mb4_unicode_ci' 'utf8mb4_unicode_ci' 'utf8mb4_unicode_ci'
db_cl_names='utf8mb4_unicode_ci' 'utf8mb4_unicode_ci' 'utf8mb4_unicode_ci'
created=1779435659270694 1779435659298109 1779435659322492
