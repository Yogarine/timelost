# timelost

Usage:

```
php bin/timelost.php [INPUT [OUTPUT]]
```

INPUT should be a CSV file with the following column headers on the first row:
'Center', 'Openings', 'Link1', 'Link2', 'Link3', 'Link4', 'Link5', 'Link6'

OUTPUT doesn't work yet. However this tool will point out obvious issues with the CSV like dupes and invalid links.