# timelost

Usage:

```
Usage: bin/timelost.php [-m=default|og|off] [-h=<rows>] -i=<input>
```

<input> should be a CSV file with the following column headers on the first row:
'Center', 'Openings', 'Link1', 'Link2', 'Link3', 'Link4', 'Link5', 'Link6'

<rows declares at which line it can find the headers, starting with 0 for the first line.
-1 means there is no header row.

There is no output yet. However this tool will point out obvious issues with the CSV like dupes and invalid links.