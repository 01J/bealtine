class Monkey
def initialize(height, weight)
@height = height
@weight = weight
end
 
def height
    @height
end
 
def weight
    @weight
end
 
def height=(height)
    @height = height
end
 
def weight=(weight)
    @weight = weight
end
      end
 
class Human < Monkey
def initialize(height, weight, name)
    super(height, weight)
    @name = name
end
 
def name
   @name
end
 
def name=(name)
   @name = name
end
end
 
m = Monkey.new(120,55)
h = Human.new(180, 90, 'Ivan')

puts "Monkey:"
puts m.weight
puts m.height
begin
	puts m.name
rescue
	puts "no name"
end
puts 
puts "Human"
puts h.weight
puts h.height
puts h.name

=begin
class BlackHuman < Human
def initialize(height, weight, name)
   super(height, weight, name)
   @skin_color = :black
end
 
def skin_color
   @skin_color
end
 
def skin_color=(skin_color)
   @skin_color = skin_color
end
end
=end
