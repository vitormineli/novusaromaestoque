#!/usr/bin/env python3
# Novus Aroma Estoque - Python Backend Helper
# This file provides backend functionality for the perfume inventory system

class NovusAromaEstoque:
    """Main class for perfume inventory management system"""
    
    def __init__(self):
        self.perfumes = []
        self.vendas = []
        self.perfume_id_counter = 1
        self.vendas_id_counter = 1
    
    def add_perfume(self, nome, marca, preco, estoque, ml, categoria, imagem=None):
        """Add a new perfume to the inventory"""
        perfume = {
            'id': self.perfume_id_counter,
            'nome': nome,
            'marca': marca,
            'preco': float(preco),
            'estoque': int(estoque),
            'ml': int(ml),
            'categoria': categoria,
            'imagem': imagem
        }
        self.perfumes.append(perfume)
        self.perfume_id_counter += 1
        return perfume
    
    def update_perfume(self, id, nome=None, marca=None, preco=None, estoque=None, ml=None, categoria=None, imagem=None):
        """Update an existing perfume in the inventory"""
        for perfume in self.perfumes:
            if perfume['id'] == id:
                if nome is not None:
                    perfume['nome'] = nome
                if marca is not None:
                    perfume['marca'] = marca
                if preco is not None:
                    perfume['preco'] = float(preco)
                if estoque is not None:
                    perfume['estoque'] = int(estoque)
                if ml is not None:
                    perfume['ml'] = int(ml)
                if categoria is not None:
                    perfume['categoria'] = categoria
                if imagem is not None:
                    perfume['imagem'] = imagem
                return perfume
        return None
    
    def delete_perfume(self, id):
        """Delete a perfume from the inventory"""
        for i, perfume in enumerate(self.perfumes):
            if perfume['id'] == id:
                return self.perfumes.pop(i)
        return None
    
    def get_perfume(self, id):
        """Get a specific perfume by ID"""
        for perfume in self.perfumes:
            if perfume['id'] == id:
                return perfume
        return None
    
    def get_perfumes(self, categoria=None, search_term=None):
        """Get all perfumes, optionally filtered by category and search term"""
        result = self.perfumes
        
        if categoria and categoria != "Todos":
            result = [p for p in result if p['categoria'] == categoria]
            
        if search_term:
            search_term = search_term.lower()
            result = [p for p in result if search_term in p['nome'].lower() or search_term in p['marca'].lower()]
            
        return result
    
    def add_venda(self, product_id, quantity, unit_price, customer_name, customer_phone=None, 
                 customer_instagram=None, customer_email=None, posted_instagram="Não",
                 sales_channel="Instagram", delivery_location="Retirada na Loja",
                 payment_method="PIX", installments=1, card_fee=0, sale_notes=None):
        """Register a new sale"""
        product = self.get_perfume(product_id)
        if not product:
            return None
            
        if product['estoque'] < quantity:
            return None
            
        # Update stock
        product['estoque'] -= quantity
        
        # Create sale record
        sale = {
            'id': self.vendas_id_counter,
            'productId': product_id,
            'productName': product['nome'],
            'productBrand': product['marca'],
            'quantity': quantity,
            'unitPrice': unit_price,
            'total': unit_price * quantity,
            'customerName': customer_name,
            'customerPhone': customer_phone,
            'customerInstagram': customer_instagram,
            'customerEmail': customer_email,
            'postedInstagram': posted_instagram,
            'salesChannel': sales_channel,
            'deliveryLocation': delivery_location,
            'paymentMethod': payment_method,
            'installments': installments,
            'cardFee': card_fee,
            'saleNotes': sale_notes,
            'date': self.get_current_datetime()
        }
        
        self.vendas.append(sale)
        self.vendas_id_counter += 1
        
        return sale
    
    def get_sales(self, search_term=None, channel=None, payment=None):
        """Get all sales, optionally filtered"""
        result = self.vendas
        
        if channel and channel != "Todos":
            result = [s for s in result if s['salesChannel'] == channel]
            
        if payment and payment != "Todos":
            result = [s for s in result if s['paymentMethod'] == payment]
            
        if search_term:
            search_term = search_term.lower()
            result = [s for s in result if search_term in s['customerName'].lower() 
                     or search_term in s['productName'].lower()]
            
        return result
    
    def get_total_items(self):
        """Get total items in inventory"""
        return sum(p['estoque'] for p in self.perfumes)
    
    def get_total_value(self):
        """Get total inventory value"""
        return sum(p['preco'] * p['estoque'] for p in self.perfumes)
    
    def get_sales_stats(self):
        """Get sales statistics"""
        today = self.get_current_date()
        
        today_sales = sum(s['total'] for s in self.vendas if s['date'].split('T')[0] == today)
        total_sales = sum(s['total'] for s in self.vendas)
        items_sold = sum(s['quantity'] for s in self.vendas)
        
        # Count unique customers
        customer_names = set()
        for sale in self.vendas:
            if sale['customerName']:
                customer_names.add(sale['customerName'])
        
        return {
            'today_sales': today_sales,
            'total_sales': total_sales,
            'items_sold': items_sold,
            'unique_customers': len(customer_names)
        }
    
    def get_current_datetime(self):
        """Get current datetime in ISO format"""
        from datetime import datetime
        return datetime.now().isoformat()
    
    def get_current_date(self):
        """Get current date in YYYY-MM-DD format"""
        from datetime import date
        return date.today().isoformat()

# Example usage
if __name__ == "__main__":
    # Create an instance of NovusAromaEstoque
    novusaromaestoque = NovusAromaEstoque()
    
    # Add sample perfumes
    novusaromaestoque.add_perfume("Chanel N°5", "Chanel", 450.00, 12, 100, "Feminino")
    novusaromaestoque.add_perfume("Sauvage", "Dior", 380.00, 8, 100, "Masculino")
    
    # Print all perfumes
    print("Perfumes in inventory:")
    for perfume in novusaromaestoque.get_perfumes():
        print(f"- {perfume['nome']} ({perfume['marca']}): {perfume['estoque']} units")
    
    # Get inventory statistics
    total_items = novusaromaestoque.get_total_items()
    total_value = novusaromaestoque.get_total_value()
    
    print(f"\nInventory summary:")
    print(f"- Total items: {total_items}")
    print(f"- Total value: R$ {total_value:.2f}")