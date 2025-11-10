export function useFormatting() {
    
    const formatCurrency = (value) => {
        if (value === null || value === undefined) {
            return '';
        }
        
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    };

    return {
        formatCurrency
    };
}