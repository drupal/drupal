UPDATE=update.txt

clean:
	rm -f *~ DEADJOE core

backup:
	@echo "- Generating $(UPDATE) ..."
	@echo "This tarball has been created on:" > $(UPDATE)
	@date >> $(UPDATE)
	@echo "- Removing older archives ..."
	@rm -f drop.tar.gz
	@echo "- Archiving PHP files ..."
	@tar -cf drop.tar *
	@gzip drop.tar
	@cp -f drop.tar.gz /home/dries/backup
	@echo "- A fresh archive is now available at http://www.drop.org/drop.tar.gz."
	@echo "  (MySQL backup NOT included.)"