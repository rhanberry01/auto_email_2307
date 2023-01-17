/****************************************************************************
Author: Joe Hunt
Name: Revenue / Cost Accruals v2.2
Free software under GNU GPL
*****************************************************************************/

/////////////////
// Installation

1) If you haven't already, unzip the file 'accruals22.zip'.
2) In FrontAccounting, choose Setup > Install/Activate Extensions.
3) Recommended settings for installation:
   - Name: Revenue / Cost Accruals
   - Folder: accruals (should follow unix folder convention)
   - Menu Tab: Banking and General Ledger
   - Menu Link Text: Revenue / Cost Accruals
   - Module file: accruals.php  (from unzipped extension file)
   - Access Levels Extensions: acc_levels.php  (from unzipped extension file)
   - Click "Add new" (or "Update" if upgrading from a previous version)
4) Make sure the extension is activated by clicking the "Extensions" drop-down
   box at the top of the page and selecting "Activated for..."
5) Copy the file trans.php to the installation folder. 
6) Go int Access Setup and select the roles and mark the new Revenue / 
   Cost accrusls that is under Banking & GL Transaction..

////////////////
// Operations

This extension performs Revenue / Cost Accruals.
Revenue / Cost Accruals is a way of distributing the revenue / cost
accruals into periods where the revenue / cost should have been taken.

Ex.
You pay a yearly Insurance for your stocks for $2000. This is paid in January.
Credit Bank Account $2000. Debit would be an accrued balance account in the
assets section. Not the normal expense account for insuranse.

Now you decide to Accrual this expense from your accrued balance account into
the expense account for insuranse during 12 periods of 1 month.

Go into Banking and General Ledger, Revenue / Cost Accrual that would be on
the right hand side below.

Check that the date is correct (should be the same day as the first booking
of the amount $2000. 
First select the accrued balance account you used.
Then you can search the amount, by clicking on the search image beside the amount.

Here you see the last transactions for the previous month up to the date and you
should find your $2000 amount. Click on the the trans_no number. Now the popup window
disappear and your date field and amount field are automatically filled out.

Now it's time to decide the frequency (normally this is a month, but you can
choose from Weekly, Bi-weekly, Monthly and Quarterly). 
And the number of periods you want this frequency to appear.

Before you click the Process Accruals you can press Show GL rows, to see if
everything seems right. If so, you can finish by clicking the Process Accruals.

Now the accruals are made for you.

The Revenue Accruals is performed the same way. Just that you choose a liability
account for the revenue accruals.

The amount would be with a minus sign.

/////////////
// Language

The language used inside this extension does NOT follow the traditional GETTEXT
translations found in most of FrontAccounting.  If you want to translate to
another language, please modify the import file directly.

/////////////////
// Improvements
 
If you make improvements to this extension, please share it with the rest of us! 
We will then incorporate it into the extension.
