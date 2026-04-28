import re

with open("resources/js/pages/app/admin/inventory/products/Index.vue", "r") as f:
    text = f.read()

# We'll extract the <template> from inventory, and modify the buttons
template_match = re.search(r'<template>.*</template>', text, re.DOTALL)
template = template_match.group(0)

# Replace 'Add Product' button
template = template.replace(
    '''@click="router.visit('/app/admin/master-data/produk')"''',
    '''@click="openCreateForm"'''
)

# Replace Edit button in table
template = template.replace(
    '''title="Edit product"
                                            @click="router.visit('/app/admin/master-data/produk')"''',
    '''title="Edit product"
                                            @click="openEditForm(product)"'''
)

# The form component templates needs to be added at the end of the template just inside AppLayout.
with open("resources/js/pages/app/admin/master-data/produk/Index.vue", "r") as f:
    master_text = f.read()

sheet_match = re.search(r'<!-- Sheet/Slide Canvas Mode -->.*<!-- Modal/Dialog Mode -->\s*(<Dialog.*?>.*?</Dialog>)\s*</div>\s*</AppLayout>', master_text, re.DOTALL)

if sheet_match:
    form_html = master_text[master_text.find('<!-- Sheet/Slide Canvas Mode -->'):master_text.find('</AppLayout>')]
    # Remove the last </div> and </AppLayout>
    form_html = form_html[:form_html.rfind('</div>')]
    
    # insert form_html before the last </div></AppLayout> in inventory
    # Actually wait, inventory ends with:
    #         </div>
    #     </AppLayout>
    # </template>
    
    insertion_point = template.rfind('</div>')
    new_template = template[:insertion_point] + form_html + '\n' + template[insertion_point:]
    
    # Write the new file script + template
    print("Template merged successfully.")
    with open("new_inventory_template.html", "w") as out:
        out.write(new_template)
else:
    print("Could not find forms in master data file.")

